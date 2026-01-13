# 技术栈

## 核心依赖

- PHP 7.4+ / 8.0+
- Laravel 8.x
- PostgreSQL 17.x
- Redis 7.x

## 主要扩展包

| 包 | 用途 |
|---|-----|
| laravel/sanctum | System 端 Token 认证 |
| laravel/horizon | Redis 队列监控 |
| mews/captcha | 验证码生成 |
| volcengine/ve-tos-php-sdk | 火山引擎对象存储 |
| guzzlehttp/guzzle | HTTP 客户端 |

## JWT 认证

项目使用自定义 `JwtHelper` 类实现 JWT，无需额外依赖：
- Admin 端密钥: `config('app.jwt_admin_secret')`
- App 端密钥: `config('app.jwt_app_secret')`

## 常用命令

```bash
# 安装依赖
composer install

# 生成应用密钥
php artisan key:generate

# 运行迁移
php artisan migrate

# 清除缓存
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# 队列监控
php artisan horizon

# 运行测试
php artisan test
./vendor/bin/phpunit
```

## 配置文件

- `.env` - 环境变量
- `config/filesystems.php` - 文件存储配置
- `config/captcha.php` - 验证码配置
