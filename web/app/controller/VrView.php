<?php

namespace app\controller;

use app\BaseController;
use app\Request;
use think\facade\View;
use think\facade\Cookie;
use think\facade\Db;

class VrView extends BaseController
{
    // 查看全景图片
    public function index(Request $request)
    {
        $id = $request->param('id');
        // 查询图片信息
        $photo = Db::name('vr_photos')
            ->alias('p')
            ->join('users u', 'p.user_id = u.id')
            ->field('p.*, u.username')
            ->find($id);
        
        if (!$photo) {
            return abort(404, '图片不存在');
        }
        
        // 检查访问权限
        if ($photo['is_public'] != 1) {
            // 检查用户是否登录
            $userAuth = Cookie::get('user_auth');
            if (empty($userAuth)) {
                return abort(403, '无权访问');
            }
            
            $userInfo = json_decode($userAuth, true);
            // 检查是否是图片所有者
            if ($photo['user_id'] != $userInfo['user_id']) {
                return abort(403, '无权访问');
            }
        }
        
        // 增加浏览次数
        Db::name('vr_photos')->where('id', $id)->inc('view_count')->update();
        
        // 查询当前图片的热点信息
        $hotspots = Db::name('vr_hotspots')
            ->alias('h')
            ->join('vr_photos p', 'h.target_photo_id = p.id')
            ->field('h.*, p.title as target_title')
            ->where('h.photo_id', $id)
            ->select();
        
        return View::fetch('vr_view/index', [
            'photo' => $photo,
            'hotspots' => $hotspots
        ]);
    }
    
    // 热点编辑页面
    public function edit(Request $request)
    {
        $id = $request->param('id');
        // 查询图片信息
        $photo = Db::name('vr_photos')
            ->alias('p')
            ->join('users u', 'p.user_id = u.id')
            ->field('p.*, u.username')
            ->find($id);
        
        if (!$photo) {
            return abort(404, '图片不存在');
        }
        
        // 检查用户是否登录
        $userAuth = Cookie::get('user_auth');
        if (empty($userAuth)) {
            return redirect('/user/login')->with('msg', '请先登录');
        }
        
        $userInfo = json_decode($userAuth, true);
        
        // 检查是否是图片所有者
        if ($photo['user_id'] != $userInfo['user_id']) {
            return abort(403, '无权访问');
        }
        
        // 查询当前图片的热点信息
        $hotspots = Db::name('vr_hotspots')
            ->alias('h')
            ->join('vr_photos p', 'h.target_photo_id = p.id')
            ->field('h.*, p.title as target_title')
            ->where('h.photo_id', $id)
            ->select();
        
        return View::fetch('vr_view/edit', [
            'photo' => $photo,
            'hotspots' => $hotspots
        ]);
    }
    
    // 添加热点
    public function addHotspot(Request $request)
    {
        // 检查用户是否登录
        $userAuth = Cookie::get('user_auth');
        if (empty($userAuth)) {
            return json(['code' => 401, 'msg' => '请先登录']);
        }
        
        $userInfo = json_decode($userAuth, true);
        
        if ($request->isPost()) {
            $photoId = $request->post('photo_id');
            $targetPhotoId = $request->post('target_photo_id');
            $latitude = $request->post('latitude');
            $longitude = $request->post('longitude');
            $title = $request->post('title', '');
            
            // 检查图片是否存在
            $photo = Db::name('vr_photos')->find($photoId);
            if (!$photo) {
                return json(['code' => 404, 'msg' => '图片不存在']);
            }
            
            // 检查目标图片是否存在
            $targetPhoto = Db::name('vr_photos')->find($targetPhotoId);
            if (!$targetPhoto) {
                return json(['code' => 404, 'msg' => '目标图片不存在']);
            }
            
            // 检查权限（必须是图片所有者）
            if ($photo['user_id'] != $userInfo['user_id']) {
                return json(['code' => 403, 'msg' => '无权操作']);
            }
            
            // 检查是否跳转到自己
            if ($photoId == $targetPhotoId) {
                return json(['code' => 400, 'msg' => '不能跳转到自己']);
            }
            
            // 添加热点
            $data = [
                'photo_id' => $photoId,
                'target_photo_id' => $targetPhotoId,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'title' => $title
            ];
            
            $hotspotId = Db::name('vr_hotspots')->insertGetId($data);
            
            if ($hotspotId) {
                return json(['code' => 200, 'msg' => '添加成功', 'data' => ['id' => $hotspotId]]);
            } else {
                return json(['code' => 500, 'msg' => '添加失败']);
            }
        }
        
        return json(['code' => 400, 'msg' => '无效请求']);
    }
    
    // 删除热点
    public function deleteHotspot(Request $request)
    {
        // 检查用户是否登录
        $userAuth = Cookie::get('user_auth');
        if (empty($userAuth)) {
            return json(['code' => 401, 'msg' => '请先登录']);
        }
        
        $userInfo = json_decode($userAuth, true);
        $hotspotId = $request->post('hotspot_id');
        
        // 查询热点信息
        $hotspot = Db::name('vr_hotspots')
            ->alias('h')
            ->join('vr_photos p', 'h.photo_id = p.id')
            ->field('h.*, p.user_id')
            ->find($hotspotId);
        
        if (!$hotspot) {
            return json(['code' => 404, 'msg' => '热点不存在']);
        }
        
        // 检查权限（必须是图片所有者）
        if ($hotspot['user_id'] != $userInfo['user_id']) {
            return json(['code' => 403, 'msg' => '无权操作']);
        }
        
        // 删除热点
        $result = Db::name('vr_hotspots')->delete($hotspotId);
        
        if ($result) {
            return json(['code' => 200, 'msg' => '删除成功']);
        } else {
            return json(['code' => 500, 'msg' => '删除失败']);
        }
    }
    
    // 获取用户的所有图片（用于热点选择）
    public function getUserPhotos(Request $request)
    {
        // 检查用户是否登录
        $userAuth = Cookie::get('user_auth');
        if (empty($userAuth)) {
            return json(['code' => 401, 'msg' => '请先登录']);
        }
        
        $userInfo = json_decode($userAuth, true);
        
        // 查询当前用户的所有图片
        $photos = Db::name('vr_photos')
            ->field('id, title')
            ->where('user_id', $userInfo['user_id'])
            ->select();
        
        return json(['code' => 200, 'msg' => '获取成功', 'data' => $photos]);
    }
}