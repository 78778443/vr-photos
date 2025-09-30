<?php
declare(strict_types=1);

namespace app\middleware;

/**
 * 静态资源缓存中间件
 * 为图片等静态资源添加适当的缓存头
 */
class StaticCache
{
    /**
     * 处理静态资源缓存
     *
     * @param \think\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        $response = $next($request);
        
        // 获取请求路径
        $path = $request->path();
        
        // 检查是否为图片资源
        if ($this->isImageResource($path)) {
            // 设置缓存头
            $response->header([
                'Cache-Control' => 'public, max-age=3600', // 缓存1小时
                'Expires' => gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT'
            ]);
        }
        
        // 检查是否为缩略图资源
        if ($this->isThumbnailResource($path)) {
            // 缩略图缓存时间可以更长，因为它们很少更改
            $response->header([
                'Cache-Control' => 'public, max-age=86400', // 缓存24小时
                'Expires' => gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT'
            ]);
        }
        
        // 检查是否为CSS/JS资源
        if ($this->isStaticResource($path)) {
            $response->header([
                'Cache-Control' => 'public, max-age=604800', // 缓存7天
                'Expires' => gmdate('D, d M Y H:i:s', time() + 604800) . ' GMT'
            ]);
        }
        
        return $response;
    }
    
    /**
     * 判断是否为图片资源
     *
     * @param string $path
     * @return bool
     */
    private function isImageResource(string $path): bool
    {
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return in_array($extension, $imageExtensions);
    }
    
    /**
     * 判断是否为缩略图资源
     *
     * @param string $path
     * @return bool
     */
    private function isThumbnailResource(string $path): bool
    {
        // 检查路径是否包含thumbnails或thumb关键词
        return strpos($path, 'thumbnail') !== false || 
               strpos($path, 'thumb') !== false ||
               preg_match('/\/thumbs?\//', $path);
    }
    
    /**
     * 判断是否为CSS/JS等静态资源
     *
     * @param string $path
     * @return bool
     */
    private function isStaticResource(string $path): bool
    {
        $staticExtensions = ['css', 'js', 'woff', 'woff2', 'ttf', 'eot', 'svg'];
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return in_array($extension, $staticExtensions);
    }
}