<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\App\PostController;

/*
|--------------------------------------------------------------------------
| 动态相关路由
|--------------------------------------------------------------------------
*/

Route::prefix('v1/post')->group(function () {
    // 动态列表（公开接口，无需登录）
    Route::get('list', [PostController::class, 'list']);

    // 动态详情（公开接口，无需登录）
    Route::get('detail/{id}', [PostController::class, 'detail'])->where('id', '[0-9]+');
});
