<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

\think\facade\Config::set(['app_dispatch_on' => true], 'app');

// 应用公共文件

/**
 * 渲染导航栏
 * @param string $currentPage 当前页面标识
 * @return string 导航栏HTML
 */
function renderNavbar($currentPage = '') {
    // 传递当前页面标识到导航栏模板
    $vars = ['page' => $currentPage];
    return \think\facade\View::fetch('common/navbar', $vars);
}