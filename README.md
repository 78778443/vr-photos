# VR全景图片浏览系统

[![License](https://img.shields.io/badge/license-Apache%202-blue.svg)](https://github.com/top-think/think/blob/8.0/LICENSE.txt)
[![ThinkPHP](https://img.shields.io/badge/ThinkPHP-8.0-green.svg)](https://github.com/top-think/think)
[![A-Frame](https://img.shields.io/badge/A--Frame-1.4.0-orange.svg)](https://aframe.io/)

VR全景图片浏览系统是一个基于Web的360度全景图片展示和管理系统，支持用户上传、管理、浏览和分享VR全景图片。

## 功能特性

### 核心功能
- **全景图片浏览**：基于WebGL的360度全景图片展示
- **VR设备支持**：兼容主流VR头显设备
- **热点导航**：在全景图片间创建跳转链接
- **响应式设计**：适配桌面端和移动端设备

### 用户管理
- 用户注册和登录系统
- 个人资料管理
- 权限控制（公开/私有图片）

### 图片管理
- 全景图片上传（支持JPG、PNG格式）
- 批量上传功能
- 图片标签管理
- 图片分类相册
- 缩略图自动生成
- 图片信息编辑（标题、描述等）

### 相册系统
- 个人相册创建和管理
- 相册封面设置
- 图片归类到相册
- 相册权限设置

### 分享功能
- 生成分享链接
- 嵌入代码生成
- 社交媒体分享

## 技术架构

### 前端技术
- **框架**：Bootstrap 5
- **VR引擎**：A-Frame 1.4.0
- **交互库**：jQuery 3.6.0
- **响应式设计**：移动优先的响应式布局

### 后端技术
- **框架**：ThinkPHP 8.0
- **数据库**：SQLite（可轻松切换为MySQL等）
- **模板引擎**：原生PHP模板
- **文件存储**：本地文件系统

### 系统要求
- PHP 8.0+
- Composer
- MySQL等数据库
- GD或Imagick扩展（用于图片处理）

## 安装部署

详细安装说明请查看 [安装文档](docs/INSTALL.md)


### Web服务器配置
#### Apache
确保已启用 mod_rewrite 模块，项目包含 .htaccess 文件。

#### Nginx
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/public;
    index index.php index.html;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 目录权限
确保以下目录具有写权限：
- `public/uploads/`
- `public/uploads/thumbnails/`
- `runtime/`

## 使用指南

详细使用说明请查看 [用户手册](docs/user_manual.md)

### 快速开始
1. 访问网站首页
2. 注册新用户账号
3. 登录系统
4. 上传全景图片
5. 创建相册并管理图片
6. 浏览和分享全景图片

### 上传图片
1. 点击导航栏"上传图片"
2. 拖拽图片到上传区域或点击选择文件
3. 填写图片信息（标题、描述、标签等）
4. 设置图片权限（公开/私有）
5. 点击"开始上传"

### 管理相册
1. 点击导航栏"我的相册"
2. 点击"创建相册"按钮
3. 填写相册名称和描述
4. 在相册详情页添加图片到相册

### 浏览图片
1. 点击导航栏"全景图片"浏览公开图片
2. 点击"我的图片"查看个人上传的图片
3. 点击图片进入全景浏览页面
4. 使用鼠标拖拽或触摸滑动查看不同角度

### VR模式
在支持WebVR的浏览器中，可以点击全屏按钮进入VR模式，使用VR头显设备获得沉浸式体验。

## 开发说明

### 项目结构
```
web/
├── app/                 # 应用目录
│   ├── controller/      # 控制器
│   ├── view/           # 视图模板
│   └── ...             # 其他应用文件
├── public/             # Web入口目录
│   ├── uploads/        # 上传文件目录
│   ├── css/            # 样式文件
│   ├── js/             # JavaScript文件
│   └── index.php       # 入口文件
├── runtime/            # 运行时目录
├── vendor/             # Composer依赖
├── database.sql        # 数据库结构
└── composer.json       # 依赖配置
```

### 自定义开发
1. 控制器位于 `app/controller/`
2. 视图模板位于 `app/view/`
3. 静态资源位于 `public/` 目录下对应子目录
4. 数据库操作使用ThinkPHP的Db门面

## 常见问题

### 图片无法上传
- 检查目录权限
- 确认PHP上传限制配置
- 验证文件格式是否支持

### 全景浏览黑屏
- 确认图片格式正确（equirectangular投影）
- 检查浏览器兼容性
- 查看浏览器控制台错误信息

### 数据库连接失败
- 检查数据库配置
- 确认数据库服务运行状态
- 验证数据库文件权限

## 贡献指南

欢迎提交Issue和Pull Request来改进系统：

1. Fork项目
2. 创建功能分支 (`git checkout -b feature/AmazingFeature`)
3. 提交更改 (`git commit -m 'Add some AmazingFeature'`)
4. 推送到分支 (`git push origin feature/AmazingFeature`)
5. 开启Pull Request

## 许可证

本项目基于Apache 2.0许可证发布，详见 [LICENSE](LICENSE.txt) 文件。

## 致谢

- [ThinkPHP](https://github.com/top-think/think) - 现代化的PHP开发框架
- [A-Frame](https://aframe.io/) - WebVR框架
- [Bootstrap](https://getbootstrap.com/) - 响应式前端框架
- [jQuery](https://jquery.com/) - JavaScript库

---