<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| App API Routes
|--------------------------------------------------------------------------
|
| 这里是 App 端（移动端/小程序/H5）的路由入口
| 子模块路由文件放在 routes/app/ 目录下
|
| 示例：
| - routes/app/member.php  会员相关
| - routes/app/post.php    帖子相关
| - routes/app/comment.php 评论相关
|
*/

// 加载 app 目录下的所有路由文件
foreach (glob(base_path('routes/app/*.php')) as $routeFile) {
    require $routeFile;
}
