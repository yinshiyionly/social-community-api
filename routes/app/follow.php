<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\App\FollowController;

/*
|--------------------------------------------------------------------------
| 关注模块路由
|--------------------------------------------------------------------------
*/

Route::prefix('v1/follow')->group(function () {
    // 需要登录的接口
    Route::middleware('app.auth')->group(function () {
        // 可能感兴趣的人（推荐用户）
        Route::get('recommend', [FollowController::class, 'recommend']);
        // 我关注的人列表
        Route::get('list', [FollowController::class, 'list']);
        // 关注用户
        Route::post('follow/{id}', [FollowController::class, 'follow'])
            ->where('id', '[0-9]+');
        // 取消关注
        Route::post('unfollow/{id}', [FollowController::class, 'unfollow'])
            ->where('id', '[0-9]+');
        // 关注的人的帖子列表
        Route::get('posts', [FollowController::class, 'posts']);
    });
});
