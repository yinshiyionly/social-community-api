<?php

use App\Http\Controllers\Admin\PostController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Post Routes
|--------------------------------------------------------------------------
|
| 后台帖子管理路由（仅查询能力）
|
*/

Route::middleware('system.auth')->group(function () {
    Route::prefix('post')->group(function () {
        // 帖子列表
        Route::get('/list', [PostController::class, 'list']);

        // 帖子审核（通过/拒绝）
        Route::put('/audit', [PostController::class, 'audit']);

        // 帖子详情
        Route::get('/{postId}', [PostController::class, 'show'])
            ->where('postId', '[0-9]+');
    });
});
