<?php

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;
use Exception;
use RuntimeException;

class AutoTag extends Command
{
    protected function configure()
    {
        $this->setName('photo:autotag')
            ->setDescription('为图片自动生成标签');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('开始为图片自动生成标签...');

        // 获取所有没有标签的图片
        $photos = Db::name('vr_photos')
            ->alias('p')
            ->leftJoin('vr_photo_tags pt', 'p.id = pt.photo_id')
            ->whereNull('pt.photo_id')
            ->select();

        $output->writeln('找到 ' . count($photos) . ' 张未标记的图片');

        foreach ($photos as $photo) {
            $output->writeln('正在处理: ' . $photo['title']);

            // 使用AI生成标签
            $tags = $this->generateTagsWithAI($photo);

            if (!empty($tags)) {
                $this->processTags($photo['id'], $tags);
                $output->writeln('  已添加标签: ' . implode(', ', $tags));
            } else {
                $output->writeln('  未生成标签');
            }
        }

        $output->writeln('标签生成完成!');
    }

    /**
     * 使用阿里云视觉AI生成标签
     */
    private function generateTagsWithAI($photo)
    {
        // 从环境变量获取阿里云API Key
        $apiKey = env('DASHSCOPE_API_KEY');

        if (!$apiKey) {
            throw new RuntimeException('请配置阿里云DashScope API Key');
        }

        // 构建图片路径
        $imagePath = root_path() . 'public/' . $photo['file_path'];
        
        // 检查文件是否存在
        if (!file_exists($imagePath)) {
            return [];
        }
        
        // 压缩图片
        $compressedImagePath = $this->compressImage($imagePath);
        
        // 将图片转换为base64
        $base64Image = base64_encode(file_get_contents($compressedImagePath));
        
        // 删除压缩后的临时图片
        unlink($compressedImagePath);

        // 准备请求数据
        $postData = [
            'model' => 'qwen3-vl-plus',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'image_url',
                            'image_url' => ['url' => 'data:image/jpeg;base64,' . $base64Image]
                        ],
                        [
                            'type' => 'text',
                            'text' => '请为这张图片生成标签，只输出标签，用逗号分隔，不要其他文字'
                        ]
                    ]
                ]
            ]
        ];

        // 使用cURL发送请求到阿里云DashScope API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://dashscope.aliyuncs.com/compatible-mode/v1/chat/completions');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode === 200) {
            $result = json_decode($response, true);

            if (isset($result['choices'][0]['message']['content'])) {
                $content = $result['choices'][0]['message']['content'];
                // 将返回的标签字符串转换为数组
                $tags = array_filter(array_map('trim', explode(',', $content)));
                return $tags;
            }
        }

        return [];
    }

    /**
     * 压缩图片以减小文件大小
     * 
     * @param string $inputPath 原始图片路径
     * @param int $quality 压缩质量 (1-100)
     * @return string 压缩后图片的路径
     */
    private function compressImage($inputPath, $quality = 85)
    {
        // 创建临时文件路径
        $tempPath = tempnam(sys_get_temp_dir(), 'compressed_') . '.jpg';
        
        // 获取图片信息
        $imageInfo = getimagesize($inputPath);
        $imageWidth = $imageInfo[0];
        $imageHeight = $imageInfo[1];
        $mimeType = $imageInfo['mime'];
        
        // 根据图片类型创建图像资源
        switch ($mimeType) {
            case 'image/jpeg':
                $srcImage = imagecreatefromjpeg($inputPath);
                break;
            case 'image/png':
                $srcImage = imagecreatefrompng($inputPath);
                break;
            case 'image/gif':
                $srcImage = imagecreatefromgif($inputPath);
                break;
            default:
                // 不支持的格式，直接返回原图
                return $inputPath;
        }
        
        // 计算新尺寸，保持宽高比
        $maxWidth = 1024;
        $maxHeight = 1024;
        
        $ratio = min($maxWidth/$imageWidth, $maxHeight/$imageHeight);
        $newWidth = $ratio < 1 ? intval($imageWidth * $ratio) : $imageWidth;
        $newHeight = $ratio < 1 ? intval($imageHeight * $ratio) : $imageHeight;
        
        // 创建新的图像资源
        $dstImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // 处理透明背景 (针对PNG)
        if ($mimeType == 'image/png') {
            imagealphablending($dstImage, false);
            imagesavealpha($dstImage, true);
            $transparent = imagecolorallocatealpha($dstImage, 255, 255, 255, 127);
            imagefilledrectangle($dstImage, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        // 重采样调整图片大小
        imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $imageWidth, $imageHeight);
        
        // 保存压缩后的图片
        imagejpeg($dstImage, $tempPath, $quality);
        
        // 释放资源
        imagedestroy($srcImage);
        imagedestroy($dstImage);
        
        return $tempPath;
    }

    /**
     * 处理标签
     */
    private function processTags($photoId, $tags)
    {
        foreach ($tags as $tagName) {
            if (empty($tagName)) {
                continue;
            }

            // 查找或创建标签
            $tag = Db::name('tags')->where('name', $tagName)->find();
            if (!$tag) {
                $tagId = Db::name('tags')->insertGetId(['name' => $tagName]);
            } else {
                $tagId = $tag['id'];
            }

            // 关联图片和标签
            $exists = Db::name('vr_photo_tags')
                ->where('photo_id', $photoId)
                ->where('tag_id', $tagId)
                ->find();

            if (!$exists) {
                Db::name('vr_photo_tags')->insert([
                    'photo_id' => $photoId,
                    'tag_id' => $tagId
                ]);
            }
        }
    }
}