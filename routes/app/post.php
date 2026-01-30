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
        // 动态列表-普通分页-在用
        Route::get('feed', [PostController::class, 'page']);
        // 动态列表（游标分页）
        Route::get('list', [PostController::class, 'list']);
        // 动态列表（普通分页）
        Route::get('page', [PostController::class, 'page']);
        // 动态详情
        Route::get('detail/{id}', [PostController::class, 'detail'])->where('id', '[0-9]+');
    });

    // 需要登录的接口
    Route::middleware('app.auth')->group(function () {
        // 发表图文动态
        Route::post('store/imageText', [PostController::class, 'storeImageText']);
        // 发表视频动态
        Route::post('store/video', [PostController::class, 'storeVideo']);
        // 发表文章动态
        Route::post('store/article', [PostController::class, 'storeArticle']);
        // 发表帖子（已废弃，建议使用新接口）
        Route::post('store', [PostController::class, 'store']);
        // 收藏帖子
        Route::post('collect', [PostController::class, 'collect']);
        // 取消收藏
        Route::post('uncollect', [PostController::class, 'uncollect']);
        // 点赞帖子
        Route::post('like', [PostController::class, 'like']);
        // 取消点赞
        Route::post('unlike', [PostController::class, 'unlike']);
    });
});
