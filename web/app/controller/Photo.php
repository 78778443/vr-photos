<?php

namespace app\controller;

use app\BaseController;
use app\ThumbnailOptimizer;
use think\facade\View;
use think\facade\Cookie;
use think\facade\Db;
use think\facade\Upload;
use think\facade\Config;

class Photo extends BaseController
{
    // 上传全景图片页面
    public function upload()
    {
        // 检查用户是否登录
        $userAuth = Cookie::get('user_auth');
        if (empty($userAuth)) {
            return redirect('/user/login')->with('msg', '请先登录');
        }
        
        $userInfo = json_decode($userAuth, true);

        if ($this->request->isPost()) {
            $title = $this->request->post('title');
            $description = $this->request->post('description');
            $isPublic = $this->request->post('is_public', 1);
            
            // 获取上传的文件
            $file = $this->request->file('photo');
            
            // 检查标题
            if (empty($title)) {
                return json(['code' => 400, 'msg' => '请填写标题']);
            }
            
            // 检查文件
            if (empty($file)) {
                return json(['code' => 400, 'msg' => '请选择要上传的图片文件']);
            }
            
            // 验证文件是否有效
            if (!$file->isValid()) {
                return json(['code' => 400, 'msg' => '上传的文件无效']);
            }
            
            // 检查文件大小
            $fileSize = $file->getSize();
            if ($fileSize == 0) {
                return json(['code' => 400, 'msg' => '上传的文件为空']);
            }
            
            // 获取配置的最大文件大小
            $maxSize = Config::get('app.upload.max_size', 50 * 1024 * 1024);
            if ($fileSize > $maxSize) {
                $maxSizeMB = round($maxSize / 1024 / 1024, 2);
                return json(['code' => 400, 'msg' => '文件大小超过限制，最大允许' . $maxSizeMB . 'MB']);
            }
            
            try {
                // 使用ThinkPHP的文件系统保存文件
                $savename = \think\facade\Filesystem::putFile('vr_photos', $file);
                $filePath = 'uploads/' . $savename;
                
                // 生成缩略图
                $thumbnailDir = 'uploads/thumbnails/';
                // 使用带时间戳的缩略图文件名，便于缓存控制
                $thumbnailName = ThumbnailOptimizer::generateThumbnailName(root_path() . 'public/' . $filePath);
                $thumbnailPath = $thumbnailDir . $thumbnailName;
                
                // 创建缩略图优化器实例
                $optimizer = new ThumbnailOptimizer(root_path() . 'public/' . $filePath, root_path() . 'public/' . $thumbnailPath);
                
                // 生成缩略图
                if (!$optimizer->generateThumbnail()) {
                    $thumbnailPath = $filePath; // 如果缩略图生成失败，使用原图
                }
                
                // 尝试生成WebP格式缩略图
                $optimizer->generateWebPThumbnail();
                
                // 保存到数据库
                $data = [
                    'user_id' => $userInfo['user_id'],
                    'title' => $title,
                    'description' => $description,
                    'file_path' => $filePath,
                    'thumbnail_path' => $thumbnailPath,
                    'is_public' => $isPublic,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                $photoId = Db::name('vr_photos')->insertGetId($data);
                
                if ($photoId) {
                    return json(['code' => 200, 'msg' => '上传成功', 'redirect' => '/photo/my']);
                } else {
                    return json(['code' => 500, 'msg' => '上传失败']);
                }
            } catch (\Exception $e) {
                return json(['code' => 500, 'msg' => '上传失败：' . $e->getMessage()]);
            }
        }
        
        return View::fetch('photo/upload');
    }
    
    // 批量上传处理接口
    public function batchUpload()
    {
        // 检查用户是否登录
        $userAuth = Cookie::get('user_auth');
        if (empty($userAuth)) {
            return json(['code' => 401, 'msg' => '请先登录']);
        }
        
        $userInfo = json_decode($userAuth, true);
        
        if ($this->request->isPost()) {
            $title = $this->request->post('title');
            $description = $this->request->post('description');
            $isPublic = $this->request->post('is_public', 1);
            
            // 获取上传的文件
            $file = $this->request->file('photo');
            
            // 检查标题
            if (empty($title)) {
                return json(['code' => 400, 'msg' => '请填写标题']);
            }
            
            // 检查文件
            if (empty($file)) {
                return json(['code' => 400, 'msg' => '请选择要上传的图片文件']);
            }
            
            // 验证文件是否有效
            if (!$file->isValid()) {
                return json(['code' => 400, 'msg' => '上传的文件无效']);
            }
            
            // 检查文件大小
            $fileSize = $file->getSize();
            if ($fileSize == 0) {
                return json(['code' => 400, 'msg' => '上传的文件为空']);
            }
            
            // 获取配置的最大文件大小
            $maxSize = Config::get('app.upload.max_size', 50 * 1024 * 1024);
            if ($fileSize > $maxSize) {
                $maxSizeMB = round($maxSize / 1024 / 1024, 2);
                return json(['code' => 400, 'msg' => '文件大小超过限制，最大允许' . $maxSizeMB . 'MB']);
            }
            
            try {
                // 使用ThinkPHP的文件系统保存文件
                $savename = \think\facade\Filesystem::putFile('vr_photos', $file);
                $filePath = 'uploads/' . $savename;
                
                // 生成缩略图
                $thumbnailDir = 'uploads/thumbnails/';
                // 使用带时间戳的缩略图文件名，便于缓存控制
                $thumbnailName = ThumbnailOptimizer::generateThumbnailName(root_path() . 'public/' . $filePath);
                $thumbnailPath = $thumbnailDir . $thumbnailName;
                
                // 创建缩略图优化器实例
                $optimizer = new ThumbnailOptimizer(root_path() . 'public/' . $filePath, root_path() . 'public/' . $thumbnailPath);
                
                // 生成缩略图
                if (!$optimizer->generateThumbnail()) {
                    $thumbnailPath = $filePath; // 如果缩略图生成失败，使用原图
                }
                
                // 尝试生成WebP格式缩略图
                $optimizer->generateWebPThumbnail();
                
                // 保存到数据库
                $data = [
                    'user_id' => $userInfo['user_id'],
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
        
        return json(['code' => 400, 'msg' => '无效的请求']);
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
        $userAuth = Cookie::get('user_auth');
        if (empty($userAuth)) {
            return redirect('/user/login')->with('msg', '请先登录');
        }
        
        $userInfo = json_decode($userAuth, true);
        
        // 查询当前用户的全景图片
        $photos = Db::name('vr_photos')
            ->where('user_id', $userInfo['user_id'])
            ->order('created_at', 'desc')
            ->paginate(12);
        
        return View::fetch('photo/my', ['photos' => $photos]);
    }
    
    // 删除全景图片
    public function delete($id)
    {
        // 检查用户是否登录
        $userAuth = Cookie::get('user_auth');
        if (empty($userAuth)) {
            return json(['code' => 401, 'msg' => '请先登录']);
        }
        
        $userInfo = json_decode($userAuth, true);
        
        // 查询要删除的图片
        $photo = Db::name('vr_photos')->find($id);
        if (!$photo) {
            return json(['code' => 404, 'msg' => '图片不存在']);
        }
        
        // 检查是否有权限删除（必须是图片的所有者）
        if ($photo['user_id'] != $userInfo['user_id']) {
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
        $shareLink = url('/VrView/' . $id, '', false, true);
        
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