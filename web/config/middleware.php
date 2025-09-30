<?php
// 中间件配置
return [
    // 别名或分组
    'alias'    => [
        'static_cache' => \app\middleware\StaticCache::class,
    ],
    // 优先级设置，此数组中的中间件会按照数组中的顺序优先执行
    'priority' => [],
    // 全局中间件定义文件
    \think\middleware\SessionInit::class,
    \app\middleware\StaticCache::class,
];
