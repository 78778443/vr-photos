<?php
declare(strict_types=1);

namespace app;

/**
 * 缩略图优化器
 * 用于生成和优化缩略图以提高页面加载性能
 */
class ThumbnailOptimizer
{
    /**
     * 最大缩略图宽度
     */
    private const MAX_WIDTH = 400;

    /**
     * 最大缩略图高度
     */
    private const MAX_HEIGHT = 300;

    /**
     * 图片质量（0-100）
     */
    private const QUALITY = 80;

    /**
     * 支持的图片格式
     */
    private const SUPPORTED_FORMATS = ['jpg', 'jpeg', 'png', 'webp'];

    /**
     * 原始图片路径
     * @var string
     */
    private string $sourcePath;

    /**
     * 缩略图保存路径
     * @var string
     */
    private string $thumbnailPath;

    /**
     * 构造函数
     * 
     * @param string $sourcePath 原始图片路径
     * @param string $thumbnailPath 缩略图保存路径
     */
    public function __construct(string $sourcePath, string $thumbnailPath)
    {
        $this->sourcePath = $sourcePath;
        $this->thumbnailPath = $thumbnailPath;
    }

    /**
     * 生成优化的缩略图
     * 
     * @return bool 是否成功生成
     */
    public function generateThumbnail(): bool
    {
        // 检查原始文件是否存在
        if (!file_exists($this->sourcePath)) {
            return false;
        }

        // 检查文件格式是否支持
        $extension = strtolower(pathinfo($this->sourcePath, PATHINFO_EXTENSION));
        if (!in_array($extension, self::SUPPORTED_FORMATS)) {
            return false;
        }

        // 创建缩略图目录（如果不存在）
        $thumbnailDir = dirname($this->thumbnailPath);
        if (!is_dir($thumbnailDir)) {
            mkdir($thumbnailDir, 0755, true);
        }

        try {
            // 获取原始图片信息
            list($width, $height, $type) = getimagesize($this->sourcePath);

            // 计算缩略图尺寸
            $newDimensions = $this->calculateDimensions($width, $height);
            $newWidth = $newDimensions['width'];
            $newHeight = $newDimensions['height'];

            // 创建画布
            $thumbnail = imagecreatetruecolor($newWidth, $newHeight);

            // 根据图片类型处理
            switch ($type) {
                case IMAGETYPE_JPEG:
                    $source = imagecreatefromjpeg($this->sourcePath);
                    // 处理透明背景（JPEG不需要）
                    $white = imagecolorallocate($thumbnail, 255, 255, 255);
                    imagefill($thumbnail, 0, 0, $white);
                    break;
                case IMAGETYPE_PNG:
                    $source = imagecreatefrompng($this->sourcePath);
                    // 处理透明背景
                    imagealphablending($thumbnail, false);
                    imagesavealpha($thumbnail, true);
                    $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
                    imagefill($thumbnail, 0, 0, $transparent);
                    break;
                case IMAGETYPE_WEBP:
                    $source = imagecreatefromwebp($this->sourcePath);
                    // 处理透明背景
                    imagealphablending($thumbnail, false);
                    imagesavealpha($thumbnail, true);
                    $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
                    imagefill($thumbnail, 0, 0, $transparent);
                    break;
                default:
                    return false;
            }

            // 调整图片大小
            imagecopyresampled(
                $thumbnail,
                $source,
                0,
                0,
                0,
                0,
                $newWidth,
                $newHeight,
                $width,
                $height
            );

            // 保存缩略图
            $result = false;
            switch ($type) {
                case IMAGETYPE_JPEG:
                    $result = imagejpeg($thumbnail, $this->thumbnailPath, self::QUALITY);
                    break;
                case IMAGETYPE_PNG:
                    // PNG压缩级别（0-9）
                    $pngQuality = (int) ((100 - self::QUALITY) / 10);
                    $result = imagepng($thumbnail, $this->thumbnailPath, $pngQuality);
                    break;
                case IMAGETYPE_WEBP:
                    // WebP质量（0-100）
                    $result = imagewebp($thumbnail, $this->thumbnailPath, self::QUALITY);
                    break;
            }

            // 释放内存
            imagedestroy($source);
            imagedestroy($thumbnail);

            return $result;
        } catch (\Exception $e) {
            // 记录错误日志
            error_log("缩略图生成失败: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 生成WebP格式的缩略图
     * 
     * @return bool 是否成功生成
     */
    public function generateWebPThumbnail(): bool
    {
        // 检查是否支持WebP
        if (!function_exists('imagewebp')) {
            return false;
        }

        // WebP缩略图路径
        $webpThumbnailPath = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $this->thumbnailPath);

        // 检查原始文件是否存在
        if (!file_exists($this->sourcePath)) {
            return false;
        }

        try {
            // 获取原始图片信息
            list($width, $height, $type) = getimagesize($this->sourcePath);

            // 计算缩略图尺寸
            $newDimensions = $this->calculateDimensions($width, $height);
            $newWidth = $newDimensions['width'];
            $newHeight = $newDimensions['height'];

            // 创建画布
            $thumbnail = imagecreatetruecolor($newWidth, $newHeight);

            // 处理透明背景
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
            $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
            imagefill($thumbnail, 0, 0, $transparent);

            // 根据图片类型处理
            switch ($type) {
                case IMAGETYPE_JPEG:
                    $source = imagecreatefromjpeg($this->sourcePath);
                    break;
                case IMAGETYPE_PNG:
                    $source = imagecreatefrompng($this->sourcePath);
                    break;
                case IMAGETYPE_WEBP:
                    $source = imagecreatefromwebp($this->sourcePath);
                    break;
                default:
                    return false;
            }

            // 调整图片大小
            imagecopyresampled(
                $thumbnail,
                $source,
                0,
                0,
                0,
                0,
                $newWidth,
                $newHeight,
                $width,
                $height
            );

            // 保存为WebP格式
            $result = imagewebp($thumbnail, $webpThumbnailPath, self::QUALITY);

            // 释放内存
            imagedestroy($source);
            imagedestroy($thumbnail);

            return $result;
        } catch (\Exception $e) {
            // 记录错误日志
            error_log("WebP缩略图生成失败: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 计算缩略图尺寸
     * 
     * @param int $width 原始宽度
     * @param int $height 原始高度
     * @return array 包含width和height的数组
     */
    private function calculateDimensions(int $width, int $height): array
    {
        // 计算缩放比例
        $ratio = min(self::MAX_WIDTH / $width, self::MAX_HEIGHT / $height);

        // 如果图片小于最大尺寸，则不放大
        if ($ratio >= 1) {
            return ['width' => $width, 'height' => $height];
        }

        // 计算新尺寸
        $newWidth = (int) ($width * $ratio);
        $newHeight = (int) ($height * $ratio);

        return ['width' => $newWidth, 'height' => $newHeight];
    }

    /**
     * 获取优化建议
     * 
     * @return array 优化建议
     */
    public function getOptimizationTips(): array
    {
        $tips = [];

        if (!file_exists($this->sourcePath)) {
            $tips[] = '原始图片不存在';
            return $tips;
        }

        // 获取图片信息
        list($width, $height, $type, $attr) = getimagesize($this->sourcePath);
        $fileSize = filesize($this->sourcePath);

        // 检查图片尺寸
        if ($width > 2000 || $height > 2000) {
            $tips[] = '图片尺寸过大，建议在上传前调整到合理尺寸';
        }

        // 检查文件大小
        if ($fileSize > 5 * 1024 * 1024) { // 5MB
            $tips[] = '图片文件过大，会影响加载速度';
        }

        // 检查格式
        $extension = strtolower(pathinfo($this->sourcePath, PATHINFO_EXTENSION));
        if ($extension === 'png' && $fileSize > 2 * 1024 * 1024) { // 2MB
            $tips[] = 'PNG格式图片较大，建议使用JPEG格式';
        }

        if (empty($tips)) {
            $tips[] = '图片已优化';
        }

        return $tips;
    }
    
    /**
     * 生成带时间戳的缩略图文件名，便于缓存控制
     * 
     * @param string $originalPath 原始文件路径
     * @return string 缩略图文件名
     */
    public static function generateThumbnailName(string $originalPath): string
    {
        $filename = pathinfo($originalPath, PATHINFO_FILENAME);
        $extension = pathinfo($originalPath, PATHINFO_EXTENSION);
        $filemtime = file_exists($originalPath) ? filemtime($originalPath) : time();
        
        // 生成带时间戳的缩略图文件名
        return $filename . '_thumb_' . $filemtime . '.' . $extension;
    }
}