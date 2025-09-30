<?php

namespace app\controller;

use app\BaseController;
use think\facade\View;
use think\facade\Session;
use think\facade\Db;
use think\facade\Upload;

class Photo extends BaseController
{
    // 上传全景图片页面
    public function upload()
    {
        // 检查用户是否登录
        if (!Session::has('user_id')) {
            return redirect('/user/login')->with('msg', '请先登录');
        }

        if ($this->request->isPost()) {
            $title = $this->request->post('title');
            $description = $this->request->post('description');
            $isPublic = $this->request->post('is_public', 1);
            
            // 获取上传的文件
            $file = $this->request->file('photo');
            
            if (empty($title) || !$file) {
                return json(['code' => 400, 'msg' => '请填写完整信息']);
            }
            
            try {
                // 移动上传的文件到指定目录
                $uploadDir = root_path() . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'vr_photos';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $savename = \think\facade\Filesystem::putFile('vr_photos', $file);
                $filePath = 'uploads/' . $savename;
                
                // 生成缩略图（简化处理，实际项目中可能需要特殊处理全景图缩略图）
                $thumbnailPath = $filePath; // 这里简化处理，实际应该生成专门的缩略图
                
                // 保存到数据库
                $data = [
                    'user_id' => Session::get('user_id'),
                    'title' => $title,
                    'description' => $description,
                    'file_path' => $filePath,
                    'thumbnail_path' => $thumbnailPath,
                    'is_public' => $isPublic,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                $photoId = Db::name('vr_photos')->insertGetId($data);
                
                if ($photoId) {
                    return json(['code' => 200, 'msg' => '上传成功']);
                } else {
                    return json(['code' => 500, 'msg' => '上传失败']);
                }
            } catch (\Exception $e) {
                return json(['code' => 500, 'msg' => '上传失败：' . $e->getMessage()]);
            }
        }
        
        return View::fetch('photo/upload');
    }
    
    // 全景图片列表页面
    public function index()
    {
        // 查询公开的全景图片
        $photos = Db::name('vr_photos')
            ->alias('p')
            ->join('users u', 'p.user_id = u.id')
            ->field('p.*, u.username')
            ->where('p.is_public', 1)
            ->order('p.created_at', 'desc')
            ->paginate(12);
        
        return View::fetch('photo/index', ['photos' => $photos]);
    }
    
    // 我的全景图片
    public function my()
    {
        // 检查用户是否登录
        if (!Session::has('user_id')) {
            return redirect('/user/login')->with('msg', '请先登录');
        }
        
        // 查询当前用户的全景图片
        $photos = Db::name('vr_photos')
            ->where('user_id', Session::get('user_id'))
            ->order('created_at', 'desc')
            ->paginate(12);
        
        return View::fetch('photo/my', ['photos' => $photos]);
    }
    
    // 删除全景图片
    public function delete($id)
    {
        // 检查用户是否登录
        if (!Session::has('user_id')) {
            return json(['code' => 401, 'msg' => '请先登录']);
        }
        
        // 查询要删除的图片
        $photo = Db::name('vr_photos')->find($id);
        if (!$photo) {
            return json(['code' => 404, 'msg' => '图片不存在']);
        }
        
        // 检查是否有权限删除（必须是图片的所有者）
        if ($photo['user_id'] != Session::get('user_id')) {
            return json(['code' => 403, 'msg' => '无权删除']);
        }
        
        // 删除数据库记录
        Db::name('vr_photos')->delete($id);
        
        // 删除文件（可选）
        /*
        $filePath = root_path() . 'public' . DIRECTORY_SEPARATOR . $photo['file_path'];
        $thumbnailPath = root_path() . 'public' . DIRECTORY_SEPARATOR . $photo['thumbnail_path'];
        if (file_exists($filePath)) unlink($filePath);
        if (file_exists($thumbnailPath) && $thumbnailPath != $filePath) unlink($thumbnailPath);
        */
        
        return json(['code' => 200, 'msg' => '删除成功']);
    }
    
    // 获取分享链接
    public function share($id)
    {
        // 查询图片信息
        $photo = Db::name('vr_photos')
            ->alias('p')
            ->join('users u', 'p.user_id = u.id')
            ->field('p.*, u.username')
            ->find($id);
        
        if (!$photo) {
            return json(['code' => 404, 'msg' => '图片不存在']);
        }
        
        // 检查是否可以分享
        if ($photo['is_public'] != 1) {
            // 可以添加更多权限检查
            // 比如检查是否是所有者等
        }
        
        // 生成分享链接
        $shareLink = url('/view/' . $id, '', false, true);
        
        return json([
            'code' => 200,
            'msg' => '获取成功',
            'data' => [
                'link' => $shareLink,
                'embed_code' => '<iframe src="' . $shareLink . '" width="800" height="600" frameborder="0" allowfullscreen></iframe>'
            ]
        ]);
    }
}