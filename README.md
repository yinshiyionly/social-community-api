# Social Community API

基于 Laravel 的社区 API 项目。

## 项目结构

```
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/          # 后台管理控制器
│   │   │   ├── App/            # App端控制器
│   │   │   └── System/         # 系统管理控制器
│   │   └── Middleware/
│   │       ├── Admin/          # 后台中间件
│   │       └── App/            # App端中间件
│   ├── Models/                 # 数据模型
│   ├── Services/               # 业务服务层
│   └── Helper/                 # 辅助工具类
├── routes/
│   ├── admin.php               # Admin 路由入口
│   ├── admin/                  # Admin 子模块路由
│   │   └── system.php          # 系统管理路由
│   ├── app.php                 # App 路由入口
│   ├── app/                    # App 子模块路由
│   │   └── (待添加)
│   └── file.php                # 文件上传路由
└── config/                     # 配置文件
```

## 路由规范

### Admin 后台管理 (`/api/*`)

路由文件位于 `routes/admin/` 目录：

| 文件 | 说明 |
|------|------|
| `system.php` | 系统管理（用户、角色、菜单、部门等） |

添加新模块时，在 `routes/admin/` 下创建对应的 `.php` 文件即可自动加载。

### App 端 (`/app/*`)

路由文件位于 `routes/app/` 目录：

| 文件 | 说明 |
|------|------|
| `member.php` | 会员相关（待创建） |
| `post.php` | 帖子相关（待创建） |

添加新模块时，在 `routes/app/` 下创建对应的 `.php` 文件即可自动加载。

## 开发指南

### 添加新路由模块

1. 在对应目录创建路由文件：
   - Admin: `routes/admin/模块名.php`
   - App: `routes/app/模块名.php`

2. 路由文件会自动被加载，无需手动注册

### 示例：创建 App 会员模块

```php
// routes/app/member.php
<?php

use Illuminate\Support\Facades\Route;

Route::prefix('member')->group(function () {
    Route::post('/login', [MemberController::class, 'login']);
    Route::post('/register', [MemberController::class, 'register']);
    
    Route::middleware('app.auth')->group(function () {
        Route::get('/profile', [MemberController::class, 'profile']);
    });
});
```

## 环境配置

复制 `.env.example` 到 `.env` 并配置数据库等信息。

```bash
cp .env.example .env
php artisan key:generate
```
