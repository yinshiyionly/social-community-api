<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\App\PostController;

/*
|--------------------------------------------------------------------------
| 动态相关路由
|--------------------------------------------------------------------------
*/

Route::prefix('v1/post')->group(function () {
    // 公开接口（可选鉴权，有 token 时返回收藏状态）
    Route::middleware('app.auth.optional')->group(function () {
        // 动态列表
        Route::get('list', [PostController::class, 'list']);
        // 动态详情
        Route::get('detail/{id}', [PostController::class, 'detail'])->where('id', '[0-9]+');
    });

    // 需要登录的接口
    Route::middleware('app.auth')->group(function () {
        // 收藏帖子
        Route::post('collect/{id}', [PostController::class, 'collect'])->where('id', '[0-9]+');
        // 取消收藏
        Route::post('uncollect/{id}', [PostController::class, 'uncollect'])->where('id', '[0-9]+');
    });
});
