<?php
namespace app\controller;

use app\BaseController;
use think\facade\View;
use think\facade\Db;

class Index extends BaseController
{
    public function index()
    {
        // 获取最新上传的图片
        $latestPhotos = Db::name('vr_photos')
            ->alias('p')
            ->join('users u', 'p.user_id = u.id')
            ->field('p.*, u.username')
            ->where('p.is_public', 1)
            ->order('p.created_at', 'desc')
            ->limit(6)
            ->select()
            ->toArray();

        // 获取热门图片（按浏览次数排序）
        $popularPhotos = Db::name('vr_photos')
            ->alias('p')
            ->join('users u', 'p.user_id = u.id')
            ->field('p.*, u.username')
            ->where('p.is_public', 1)
            ->order('p.view_count', 'desc')
            ->limit(6)
            ->select()
            ->toArray();

        return View::fetch('index', [
            'latestPhotos' => $latestPhotos,
            'popularPhotos' => $popularPhotos
        ]);
    }
}