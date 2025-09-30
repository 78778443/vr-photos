<?php

namespace app\controller;

use app\BaseController;
use think\facade\View;
use think\facade\Db;

class Vr extends BaseController
{
    public function index()
    {
        // 获取最新上传的全景图片
        $latestPhotos = Db::name('vr_photos')
            ->alias('p')
            ->join('users u', 'p.user_id = u.id')
            ->field('p.*, u.username')
            ->where('p.is_public', 1)
            ->order('p.created_at', 'desc')
            ->limit(6)
            ->select();
        
        // 获取热门全景图片（按浏览次数排序）
        $popularPhotos = Db::name('vr_photos')
            ->alias('p')
            ->join('users u', 'p.user_id = u.id')
            ->field('p.*, u.username')
            ->where('p.is_public', 1)
            ->order('p.view_count', 'desc')
            ->limit(6)
            ->select();
        
        return View::fetch('index', [
            'latestPhotos' => $latestPhotos,
            'popularPhotos' => $popularPhotos
        ]);
    }
}