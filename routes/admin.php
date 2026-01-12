<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin API Routes
|--------------------------------------------------------------------------
|
| 这里是后台管理系统的路由入口
| 子模块路由文件放在 routes/admin/ 目录下
|
| 示例：
| - routes/admin/system.php  系统管理（用户、角色、菜单等）
| - routes/admin/monitor.php 系统监控（日志等）
|
*/

// 加载 admin 目录下的所有路由文件
foreach (glob(base_path('routes/admin/*.php')) as $routeFile) {
    require $routeFile;
}
