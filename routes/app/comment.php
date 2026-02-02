<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\App\PostCommentController;

/*
|--------------------------------------------------------------------------
| 评论相关路由
|--------------------------------------------------------------------------
*/

Route::prefix('v1/comment')->group(function () {
    // 公开接口
    Route::middleware('app.auth.optional')->group(function () {
        // 获取帖子评论列表
        Route::get('post/{postId}', [PostCommentController::class, 'list'])->where('postId', '[0-9]+');
        // 获取评论的回复列表
        Route::get('{commentId}/replies', [PostCommentController::class, 'replies'])->where('commentId', '[0-9]+');
    });

    // 需要登录的接口
    Route::middleware('app.auth')->group(function () {
        // 发表评论
        Route::post('post', [PostCommentController::class, 'store']);
        // 删除评论
        Route::delete('post', [PostCommentController::class, 'destroy']);
        // 点赞评论
        Route::post('like', [PostCommentController::class, 'like']);
        // 取消点赞评论
        Route::post('unlike', [PostCommentController::class, 'unlike']);
    });
});
