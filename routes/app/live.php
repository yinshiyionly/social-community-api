<?php

use App\Http\Controllers\App\LiveController;
use App\Http\Controllers\App\LiveCourseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 搜索相关路由
|--------------------------------------------------------------------------
*/

Route::prefix('v1/live')->group(function () {
    // 需要登录的接口
    Route::middleware('app.auth')->group(function () {
        // 获取直播间信息
        Route::get('roomInfo', [LiveController::class, 'roomInfo']);
    });
    // 获取直播首页数据
    Route::get('home', [LiveCourseController::class, 'home'])->middleware('app.auth.optional');
});
