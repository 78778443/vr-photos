<?php

namespace app\controller;

use app\BaseController;
use think\facade\View;
use think\facade\Db;

class View extends BaseController
{
    // 查看全景图片
    public function index($id)
    {
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
            // 这里可以添加更多权限检查逻辑
            // 比如检查是否是图片所有者等
        }
        
        // 增加浏览次数
        Db::name('vr_photos')->where('id', $id)->inc('view_count')->update();
        
        return View::fetch('view/index', ['photo' => $photo]);
    }
}