<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\App\PostController;
use App\Http\Controllers\App\PostCommentController;

/*
|--------------------------------------------------------------------------
| 动态相关路由
|--------------------------------------------------------------------------
*/

Route::prefix('v1/post')->group(function () {
    // === 新版评论接口 ===
    // 获取帖子评论列表（普通分页）
    Route::middleware('app.auth.optional')->group(function () {
        Route::get('comments', [PostCommentController::class, 'listV2']);
    });
    Route::middleware('app.auth')->prefix('comment')->group(function () {
        // 发表评论
        Route::post('', [PostCommentController::class, 'storeV2']);
        // 点赞评论
        Route::post('like', [PostCommentController::class, 'like']);
        // 取消点赞评论
        Route::post('unlike', [PostCommentController::class, 'unlike']);
    });

    // 公开接口（可选鉴权，有 token 时返回收藏状态）
    Route::middleware('app.auth.optional')->group(function () {
        // 动态列表-普通分页-在用
        Route::get('feed', [PostController::class, 'page']);
        // 动态列表（游标分页）
        Route::get('list', [PostController::class, 'list']);
        // 动态列表（普通分页）
        Route::get('page', [PostController::class, 'page']);
        // 视频流列表（游标分页）- 刷视频场景
        Route::get('video/feed', [PostController::class, 'videoFeed']);
        // 动态详情（通用，v1）
        Route::get('detail', [PostController::class, 'detail']);
        // 动态详情（兼容旧版路径参数）
        Route::get('detail/{id}', [PostController::class, 'detailById']);
        // 图文动态详情
        Route::get('detail/imageText/{id}', [PostController::class, 'detailImageText']);
        // 视频动态详情
        Route::get('detail/video/{id}', [PostController::class, 'detailVideo']);
        // 文章动态详情
        Route::get('detail/article/{id}', [PostController::class, 'detailArticle']);
    });

    // 需要登录的接口
    Route::middleware('app.auth')->group(function () {
        // 发表图文动态
        Route::post('store/imageText', [PostController::class, 'storeImageText']);
        // 发表视频动态
        Route::post('store/video', [PostController::class, 'storeVideo']);
        // 发表文章动态
        Route::post('store/article', [PostController::class, 'storeArticle']);
        // 更新图文动态（仅作者可更新）
        Route::put('update/imageText', [PostController::class, 'updateImageText']);
        // 更新视频动态（仅作者可更新）
        Route::put('update/video', [PostController::class, 'updateVideo']);
        // 更新文章动态（仅作者可更新）
        Route::put('update/article', [PostController::class, 'updateArticle']);
        // 删除帖子（仅作者可删除）
        Route::post('delete', [PostController::class, 'delete']);
        // 发表帖子（已废弃，建议使用新接口）
        // Route::post('store', [PostController::class, 'store']);
        // 收藏帖子
        Route::post('collect', [PostController::class, 'collect']);
        // 取消收藏
        Route::post('uncollect', [PostController::class, 'uncollect']);
        // 点赞帖子
        Route::post('like', [PostController::class, 'like']);
        // 取消点赞
        Route::post('unlike', [PostController::class, 'unlike']);
        // 分享帖子（点击分享按钮即计一次）
        Route::post('share', [PostController::class, 'share']);
    });
});
