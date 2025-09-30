<?php

namespace app\controller;

use app\BaseController;
use think\facade\View;
use think\facade\Cookie;
use think\facade\Db;
use think\facade\Session;

class Album extends BaseController
{
    // 相册列表页面
    public function index()
    {
        // 检查用户是否登录
        $userAuth = Cookie::get('user_auth');
        if (empty($userAuth)) {
            return redirect('/user/login')->with('msg', '请先登录');
        }
        
        $userInfo = json_decode($userAuth, true);
        
        // 查询当前用户的所有相册
        $albums = Db::name('albums')
            ->where('user_id', $userInfo['user_id'])
            ->order('created_at', 'desc')
            ->select()
            ->toArray();
        
        // 为每个相册获取图片数量和封面图片
        foreach ($albums as &$album) {
            // 获取相册中的图片数量
            $album['photo_count'] = Db::name('vr_photo_albums')
                ->where('album_id', $album['id'])
                ->count();
            
            // 获取相册封面图片
            if ($album['cover_photo_id']) {
                $coverPhoto = Db::name('vr_photos')
                    ->where('id', $album['cover_photo_id'])
                    ->find();
                $album['cover_thumbnail'] = $coverPhoto ? $coverPhoto['thumbnail_path'] : '';
            } else {
                // 如果没有设置封面，使用相册中第一张图片作为封面
                $firstPhoto = Db::name('vr_photos')
                    ->alias('p')
                    ->join('vr_photo_albums pa', 'p.id = pa.photo_id')
                    ->where('pa.album_id', $album['id'])
                    ->find();
                $album['cover_thumbnail'] = $firstPhoto ? $firstPhoto['thumbnail_path'] : '';
            }
        }
        
        return View::fetch('album/index', ['albums' => $albums]);
    }
    
    // 创建相册页面
    public function create()
    {
        // 检查用户是否登录
        $userAuth = Cookie::get('user_auth');
        if (empty($userAuth)) {
            return redirect('/user/login')->with('msg', '请先登录');
        }

        if ($this->request->isPost()) {
            $name = $this->request->post('name');
            $description = $this->request->post('description');
            
            if (empty($name)) {
                return json(['code' => 400, 'msg' => '请输入相册名称']);
            }
            
            $userInfo = json_decode($userAuth, true);
            
            // 检查相册名称是否已存在
            $exists = Db::name('albums')
                ->where('user_id', $userInfo['user_id'])
                ->where('name', $name)
                ->find();
            
            if ($exists) {
                return json(['code' => 400, 'msg' => '相册名称已存在']);
            }
            
            // 创建相册
            $data = [
                'user_id' => $userInfo['user_id'],
                'name' => $name,
                'description' => $description,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $albumId = Db::name('albums')->insertGetId($data);
            
            if ($albumId) {
                return json(['code' => 200, 'msg' => '创建成功', 'data' => ['id' => $albumId]]);
            } else {
                return json(['code' => 500, 'msg' => '创建失败']);
            }
        }
        
        return View::fetch('album/create');
    }
    
    // 编辑相册
    public function edit()
    {
        // 获取参数
        $id = $this->request->param('id');
        
        // 检查用户是否登录
        $userAuth = Cookie::get('user_auth');
        if (empty($userAuth)) {
            return redirect('/user/login')->with('msg', '请先登录');
        }
        
        $userInfo = json_decode($userAuth, true);
        
        // 查询相册信息
        $album = Db::name('albums')
            ->where('id', $id)
            ->where('user_id', $userInfo['user_id'])
            ->find();
        
        if (!$album) {
            // 检查相册是否存在，但不属于当前用户
            $checkAlbum = Db::name('albums')->where('id', $id)->find();
            if ($checkAlbum) {
                return abort(403, '无权访问该相册');
            }
            return abort(404, '相册不存在');
        }
        
        if ($this->request->isPost()) {
            $name = $this->request->post('name');
            $description = $this->request->post('description');
            
            if (empty($name)) {
                return json(['code' => 400, 'msg' => '请输入相册名称']);
            }
            
            // 检查相册名称是否已存在（排除当前相册）
            $exists = Db::name('albums')
                ->where('user_id', $userInfo['user_id'])
                ->where('name', $name)
                ->where('id', '<>', $id)
                ->find();
            
            if ($exists) {
                return json(['code' => 400, 'msg' => '相册名称已存在']);
            }
            
            // 更新相册
            $data = [
                'name' => $name,
                'description' => $description,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $result = Db::name('albums')
                ->where('id', $id)
                ->where('user_id', $userInfo['user_id'])
                ->update($data);
            
            if ($result !== false) {
                return json(['code' => 200, 'msg' => '更新成功']);
            } else {
                return json(['code' => 500, 'msg' => '更新失败']);
            }
        }
        
        return View::fetch('album/edit', ['album' => $album]);
    }
    
    // 删除相册
    public function delete()
    {
        // 获取参数
        $id = $this->request->param('id');
        
        // 检查用户是否登录
        $userAuth = Cookie::get('user_auth');
        if (empty($userAuth)) {
            return json(['code' => 401, 'msg' => '请先登录']);
        }
        
        $userInfo = json_decode($userAuth, true);
        
        // 查询相册信息
        $album = Db::name('albums')
            ->where('id', $id)
            ->where('user_id', $userInfo['user_id'])
            ->find();
        
        if (!$album) {
            // 检查相册是否存在，但不属于当前用户
            $checkAlbum = Db::name('albums')->where('id', $id)->find();
            if ($checkAlbum) {
                return json(['code' => 403, 'msg' => '无权操作该相册']);
            }
            return json(['code' => 404, 'msg' => '相册不存在']);
        }
        
        // 开始事务
        Db::startTrans();
        try {
            // 删除相册与图片的关联关系
            Db::name('vr_photo_albums')
                ->where('album_id', $id)
                ->delete();
            
            // 删除相册
            Db::name('albums')
                ->where('id', $id)
                ->delete();
            
            // 提交事务
            Db::commit();
            
            return json(['code' => 200, 'msg' => '删除成功']);
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return json(['code' => 500, 'msg' => '删除失败：' . $e->getMessage()]);
        }
    }
    
    // 相册详情页面（查看相册中的图片）
    public function detail()
    {
        // 获取参数
        $id = $this->request->param('id');
        
        // 检查用户是否登录
        $userAuth = Cookie::get('user_auth');
        if (empty($userAuth)) {
            return redirect('/user/login')->with('msg', '请先登录');
        }
        
        $userInfo = json_decode($userAuth, true);
        
        // 查询相册信息
        $album = Db::name('albums')
            ->where('id', $id)
            ->where('user_id', $userInfo['user_id'])
            ->find();
        
        // 调试信息 - 可以帮助我们查看实际查询条件
        // echo "ID: $id, User ID: " . $userInfo['user_id'];
        // var_dump($album);
        // exit;
        
        if (!$album) {
            // 检查相册是否存在，但不属于当前用户
            $checkAlbum = Db::name('albums')->where('id', $id)->find();
            if ($checkAlbum) {
                return abort(403, '无权访问该相册');
            }
            return abort(404, '相册不存在');
        }
        
        // 查询相册中的图片
        $photos = Db::name('vr_photos')
            ->alias('p')
            ->join('vr_photo_albums pa', 'p.id = pa.photo_id')
            ->where('pa.album_id', $id)
            ->order('p.created_at', 'desc')
            ->select()
            ->toArray();
        
        // 获取每张图片的标签
        foreach ($photos as &$photo) {
            $photo['tags'] = Db::name('tags')
                ->alias('t')
                ->join('vr_photo_tags pt', 't.id = pt.tag_id')
                ->where('pt.photo_id', $photo['id'])
                ->column('t.name');
        }
        
        return View::fetch('album/detail', [
            'album' => $album,
            'photos' => $photos
        ]);
    }
    
    // 为图片添加到相册
    public function addPhotoToAlbum()
    {
        // 检查用户是否登录
        $userAuth = Cookie::get('user_auth');
        if (empty($userAuth)) {
            return json(['code' => 401, 'msg' => '请先登录']);
        }
        
        $userInfo = json_decode($userAuth, true);
        
        if ($this->request->isPost()) {
            $photoId = $this->request->post('photo_id');
            $albumId = $this->request->post('album_id');
            
            // 检查图片是否存在且属于当前用户
            $photo = Db::name('vr_photos')
                ->where('id', $photoId)
                ->where('user_id', $userInfo['user_id'])
                ->find();
            
            if (!$photo) {
                return json(['code' => 404, 'msg' => '图片不存在']);
            }
            
            // 检查相册是否存在且属于当前用户
            $album = Db::name('albums')
                ->where('id', $albumId)
                ->where('user_id', $userInfo['user_id'])
                ->find();
            
            if (!$album) {
                return json(['code' => 404, 'msg' => '相册不存在']);
            }
            
            // 检查图片是否已经在相册中
            $exists = Db::name('vr_photo_albums')
                ->where('photo_id', $photoId)
                ->where('album_id', $albumId)
                ->find();
            
            if ($exists) {
                return json(['code' => 400, 'msg' => '图片已在此相册中']);
            }
            
            // 添加图片到相册
            $result = Db::name('vr_photo_albums')
                ->insert([
                    'photo_id' => $photoId,
                    'album_id' => $albumId
                ]);
            
            if ($result) {
                // 更新相册封面（如果相册还没有封面）
                if (!$album['cover_photo_id']) {
                    Db::name('albums')
                        ->where('id', $albumId)
                        ->update(['cover_photo_id' => $photoId]);
                }
                
                return json(['code' => 200, 'msg' => '添加成功']);
            } else {
                return json(['code' => 500, 'msg' => '添加失败']);
            }
        }
        
        return json(['code' => 400, 'msg' => '无效请求']);
    }
    
    // 从相册中移除图片
    public function removePhotoFromAlbum()
    {
        // 检查用户是否登录
        $userAuth = Cookie::get('user_auth');
        if (empty($userAuth)) {
            return json(['code' => 401, 'msg' => '请先登录']);
        }
        
        $userInfo = json_decode($userAuth, true);
        
        if ($this->request->isPost()) {
            $photoId = $this->request->post('photo_id');
            $albumId = $this->request->post('album_id');
            
            // 检查图片是否存在且属于当前用户
            $photo = Db::name('vr_photos')
                ->where('id', $photoId)
                ->where('user_id', $userInfo['user_id'])
                ->find();
            
            if (!$photo) {
                return json(['code' => 404, 'msg' => '图片不存在']);
            }
            
            // 检查相册是否存在且属于当前用户
            $album = Db::name('albums')
                ->where('id', $albumId)
                ->where('user_id', $userInfo['user_id'])
                ->find();
            
            if (!$album) {
                return json(['code' => 404, 'msg' => '相册不存在']);
            }
            
            // 从相册中移除图片
            $result = Db::name('vr_photo_albums')
                ->where('photo_id', $photoId)
                ->where('album_id', $albumId)
                ->delete();
            
            if ($result) {
                // 如果这是相册封面图片，更新相册封面
                if ($album['cover_photo_id'] == $photoId) {
                    // 查找相册中的其他图片作为新封面
                    $newCover = Db::name('vr_photos')
                        ->alias('p')
                        ->join('vr_photo_albums pa', 'p.id = pa.photo_id')
                        ->where('pa.album_id', $albumId)
                        ->order('p.created_at', 'desc')
                        ->find();
                    
                    $newCoverId = $newCover ? $newCover['id'] : null;
                    
                    Db::name('albums')
                        ->where('id', $albumId)
                        ->update(['cover_photo_id' => $newCoverId]);
                }
                
                return json(['code' => 200, 'msg' => '移除成功']);
            } else {
                return json(['code' => 500, 'msg' => '移除失败']);
            }
        }
        
        return json(['code' => 400, 'msg' => '无效请求']);
    }
    
    // 获取用户的所有相册（用于选择相册）
    public function getUserAlbums()
    {
        // 检查用户是否登录
        $userAuth = Cookie::get('user_auth');
        if (empty($userAuth)) {
            return json(['code' => 401, 'msg' => '请先登录']);
        }
        
        $userInfo = json_decode($userAuth, true);
        
        // 查询当前用户的所有相册
        $albums = Db::name('albums')
            ->field('id, name')
            ->where('user_id', $userInfo['user_id'])
            ->order('created_at', 'desc')
            ->select();
        
        return json(['code' => 200, 'msg' => '获取成功', 'data' => $albums]);
    }
}