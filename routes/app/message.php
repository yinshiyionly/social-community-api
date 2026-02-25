<?php

use App\Http\Controllers\App\MessageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| App 消息模块路由
|--------------------------------------------------------------------------
*/

Route::prefix('v1/message')->middleware('app.auth')->group(function () {
    // 获取消息总列表（各分类概览）
    Route::get('/overview', [MessageController::class, 'overview']);

    // 获取消息未读数统计
    Route::get('/unreadCount', [MessageController::class, 'unreadCount']);

    // 获取赞和收藏消息列表
    // Route::get('/likeAndCollect', [MessageController::class, 'likeAndCollect']);
    Route::get('/likeMe', [MessageController::class, 'likeAndCollect']);

    // 获取评论消息列表
    // Route::get('/comment', [MessageController::class, 'comment']);
    Route::get('/commentMe', [MessageController::class, 'comment']);

    // 获取关注消息列表
    // Route::get('/follow', [MessageController::class, 'follow']);
    Route::get('/followMe', [MessageController::class, 'follow']);

    // 获取系统消息列表
    Route::get('/system', [MessageController::class, 'system']);

    // 获取小秘书消息列表
    Route::get('/secretary', [MessageController::class, 'secretary']);

    // 获取指定官方账号的消息列表（会话详情）
    Route::get('/system/sender/{senderId}', [MessageController::class, 'systemBySender'])
        ->where('senderId', '[0-9]+');

    // 获取系统消息详情
    Route::get('/system/{id}', [MessageController::class, 'systemDetail'])
        ->where('id', '[0-9]+');

    // 标记消息为已读
    Route::post('/markRead', [MessageController::class, 'markRead']);

    // 全部已读
    Route::post('/markAllRead', [MessageController::class, 'markAllRead']);
});

