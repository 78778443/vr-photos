# VR全景图片浏览系统安装说明

## 目录
1. [环境要求](#环境要求)
2. [获取源代码](#获取源代码)
3. [安装依赖](#安装依赖)
4. [配置数据库](#配置数据库)
5. [配置环境变量](#配置环境变量)
6. [初始化系统](#初始化系统)
7. [配置Web服务器](#配置web服务器)
8. [启动系统](#启动系统)
9. [AI自动标签功能配置](#ai自动标签功能配置)

## 环境要求

### 服务器环境
- PHP >= 8.0
- MySQL >= 5.7 或 MariaDB >= 10.2
- Apache >= 2.4 或 Nginx >= 1.10
- Composer >= 2.0

### PHP扩展要求
- OpenSSL PHP扩展
- PDO PHP扩展
- Mbstring PHP扩展
- Tokenizer PHP扩展
- XML PHP扩展
- GD PHP扩展 (用于图片处理)
- cURL PHP扩展 (用于API调用)

### 开发工具 (可选)
- Git (用于获取源代码)
- Node.js 和 npm (用于前端资源构建)

## 获取源代码

### 使用Git克隆仓库
```bash
git clone https://github.com/78778443/vr-photos.git
cd vr-photos/web
```

### 或者下载压缩包
1. 在GitHub页面点击"Download ZIP"
2. 解压到目标目录
3. 进入项目web目录

## 安装依赖

### 安装PHP依赖
在项目根目录(web目录)下执行:

```bash
composer install
```

如果遇到网络问题，可以使用阿里云镜像:
```bash
composer config -g repo.packagist composer https://mirrors.aliyun.com/composer/
composer install
```
 
## 配置数据库

### 创建数据库
使用MySQL客户端创建数据库:
```sql
CREATE DATABASE `360photos` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 导入数据库结构
```bash
mysql -u username -p 360photos < database.sql
```
 

## 配置环境变量

### 复制环境配置文件
```bash
cp .example.env .env
```

### 编辑.env文件
使用文本编辑器打开.env文件并修改以下配置:

```env
# 数据库配置
DB_TYPE = mysql
DB_HOST = 127.0.0.1
DB_NAME = 360photos
DB_USER = your_database_username
DB_PASS = your_database_password
DB_PORT = 3306
DB_CHARSET = utf8mb4

# 调试模式
APP_DEBUG = true

# 默认语言
DEFAULT_LANG = zh-cn

# 上传配置
UPLOAD_MAX_FILESIZE = 50M
POST_MAX_SIZE = 50M

# 阿里云DashScope API Key (用于AI自动标签功能)
DASHSCOPE_API_KEY = your_dashscope_api_key
```

### 设置文件权限
确保以下目录有写权限:
```bash
chmod -R 755 runtime/
chmod -R 755 public/uploads/
chmod -R 755 public/thumbnails/
```
  

## 启动系统

### 使用PHP内置服务器 (开发环境)
```bash
cd web
php think run 
```

然后访问 http://localhost:8000

### 生产环境
配置好Web服务器后，直接访问配置的域名即可。

## AI自动标签功能配置

### 获取阿里云API Key
1. 注册阿里云账号
2. 开通DashScope服务
3. 在DashScope控制台获取API Key

### 配置API Key
在.env文件中配置:
```env
DASHSCOPE_API_KEY = your_actual_api_key_here
```

### 使用自动标签功能
通过命令行运行自动标签命令:
```bash
php think photo:autotag
```

该命令会为系统中未标记的图片自动生成标签。

### 定时任务配置 (可选)
可以配置定时任务定期为新上传的图片生成标签:
```bash
# 每天凌晨2点执行自动标签
0 2 * * * cd /path/to/vr-photos/web && php think photo:autotag >> /var/log/360photos-autotag.log 2>&1
```

## 常见问题
 
### 2. 上传图片失败
检查public/uploads目录是否有写权限，以及PHP配置中的upload_max_filesize和post_max_size是否足够大。

### 3. 数据库连接失败
检查.env文件中的数据库配置是否正确，确保MySQL服务正在运行。

### 4. AI自动标签功能不工作
检查.env文件中的DASHSCOPE_API_KEY是否正确配置，确保阿里云账户余额充足。

### 5. 图片处理功能异常
确保PHP安装了GD扩展，并且有相应的图片处理函数支持。

## 技术支持

如果在安装过程中遇到问题，请提交GitHub Issue或通过以下方式联系:
- 邮箱: [78778443@qq.com]
- GitHub Issues: [https://github.com/78778443/vr-photos/issues]