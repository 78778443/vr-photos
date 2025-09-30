// 图片懒加载实现
class ImageLazyLoader {
    constructor() {
        this.imageObserver = null;
        this.init();
    }

    init() {
        // 检查浏览器是否支持 Intersection Observer
        if ('IntersectionObserver' in window) {
            this.imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        this.loadImage(img);
                        this.imageObserver.unobserve(img);
                    }
                });
            }, {
                rootMargin: '50px 0px' // 提前50px加载
            });
        }
    }

    loadImage(img) {
        const src = img.dataset.src;
        if (!src) return;

        // 显示加载动画
        img.classList.add('loading');

        const image = new Image();
        image.onload = () => {
            img.src = src;
            img.classList.remove('loading');
            img.classList.add('loaded');
        };
        image.onerror = () => {
            img.classList.remove('loading');
            img.classList.add('error');
            img.src = '/images/placeholder.svg'; // 占位图
        };
        image.src = src;
    }

    observeImages() {
        const images = document.querySelectorAll('img[data-src]');
        if (this.imageObserver) {
            images.forEach(img => this.imageObserver.observe(img));
        } else {
            // 如果不支持 Intersection Observer，直接加载所有图片
            images.forEach(img => {
                img.src = img.dataset.src;
            });
        }
    }
}

// 缩略图优化工具
class ThumbnailOptimizer {
    static optimize(container) {
        const thumbnails = container.querySelectorAll('.photo-thumb');
        thumbnails.forEach(thumb => {
            const originalSrc = thumb.src;
            // 创建WebP格式的缩略图URL（如果服务器支持）
            const webpSrc = originalSrc.replace(/\.(jpg|jpeg|png)$/i, '.webp');
            
            // 检查浏览器是否支持WebP
            this.checkWebPSupport().then(isSupported => {
                if (isSupported) {
                    thumb.src = webpSrc;
                }
            });
        });
    }

    static checkWebPSupport() {
        return new Promise(resolve => {
            const webP = new Image();
            webP.onload = webP.onerror = function () {
                resolve(webP.height === 2);
            };
            webP.src = 'data:image/webp;base64,UklGRjoAAABXRUJQVlA4IC4AAACyAgCdASoCAAIALmk0mk0iIiIiIgBoSygABc6WWgAA/veff/0PP8bA//LwYAAA';
        });
    }
}

// CDN优化和资源加载管理
class ResourceOptimizer {
    static init() {
        // 将部分资源切换到CDN
        this.optimizeCSS();
        this.optimizeJS();
    }

    static optimizeCSS() {
        // Bootstrap 已经使用CDN，无需更改
        // 可以添加其他CSS资源的CDN优化
    }

    static optimizeJS() {
        // jQuery 和 Bootstrap 已经使用CDN，无需更改
        // 可以添加其他JS资源的CDN优化
    }
}

// 缓存策略管理
class CacheManager {
    static setCacheHeaders() {
        // 这个功能需要在服务器端实现
        // 在这里我们只提供前端可以控制的部分缓存策略
    }

    static setCacheForImage(img, maxAge = 3600) {
        // 为单个图片设置缓存策略
        img.setAttribute('data-cache-max-age', maxAge);
    }
}

// 页面加载完成后初始化性能优化
document.addEventListener('DOMContentLoaded', function() {
    // 初始化图片懒加载
    const lazyLoader = new ImageLazyLoader();
    lazyLoader.observeImages();
    
    // 初始化缩略图优化
    ThumbnailOptimizer.optimize(document);
    
    // 初始化资源优化
    ResourceOptimizer.init();
    
    // 初始化缓存管理
    CacheManager.setCacheHeaders();
});

// 导出类以供其他脚本使用
window.ImageLazyLoader = ImageLazyLoader;
window.ThumbnailOptimizer = ThumbnailOptimizer;
window.ResourceOptimizer = ResourceOptimizer;
window.CacheManager = CacheManager;