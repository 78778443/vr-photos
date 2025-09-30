-- VR全景图片浏览系统数据库设计

-- 用户表
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `email` varchar(100) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);

-- 全景图片表
CREATE TABLE `vr_photos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `file_path` varchar(255) NOT NULL,
  `thumbnail_path` varchar(255) DEFAULT NULL,
  `view_count` int(11) DEFAULT 0,
  `is_public` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- 标签表
CREATE TABLE `tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL UNIQUE,
  PRIMARY KEY (`id`)
);

-- 全景图片标签关联表
CREATE TABLE `vr_photo_tags` (
  `photo_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  PRIMARY KEY (`photo_id`, `tag_id`),
  FOREIGN KEY (`photo_id`) REFERENCES `vr_photos`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`tag_id`) REFERENCES `tags`(`id`) ON DELETE CASCADE
);

-- 热点表（用于VR图片之间的跳转）
CREATE TABLE `vr_hotspots` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `photo_id` int(11) NOT NULL COMMENT '所属全景图片ID',
  `target_photo_id` int(11) NOT NULL COMMENT '目标全景图片ID',
  `latitude` float NOT NULL COMMENT '纬度(-90到90)',
  `longitude` float NOT NULL COMMENT '经度(-180到180)',
  `title` varchar(255) DEFAULT NULL COMMENT '热点标题',
  `icon` varchar(20) DEFAULT 'circle' COMMENT '热点图标类型',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`photo_id`) REFERENCES `vr_photos`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`target_photo_id`) REFERENCES `vr_photos`(`id`) ON DELETE CASCADE
);

-- 相册表
CREATE TABLE `albums` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `cover_photo_id` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`cover_photo_id`) REFERENCES `vr_photos`(`id`) ON DELETE SET NULL
);

-- 全景图片相册关联表
CREATE TABLE `vr_photo_albums` (
  `photo_id` int(11) NOT NULL,
  `album_id` int(11) NOT NULL,
  PRIMARY KEY (`photo_id`, `album_id`),
  FOREIGN KEY (`photo_id`) REFERENCES `vr_photos`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`album_id`) REFERENCES `albums`(`id`) ON DELETE CASCADE
);