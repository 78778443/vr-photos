-- 为热点表添加图标字段
ALTER TABLE `vr_hotspots` ADD COLUMN `icon` varchar(20) DEFAULT 'circle' COMMENT '热点图标类型';