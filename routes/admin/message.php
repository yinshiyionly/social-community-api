<?php

use App\Http\Controllers\Admin\MessageSystemController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Message Routes
|--------------------------------------------------------------------------
|
| 后台系统消息管理路由
|
*/

Route::middleware('system.auth')->group(function () {
    Route::prefix('message/system')->group(function () {
        // 官方发送者下拉选项
        Route::get('/senderOptions', [MessageSystemController::class, 'senderOptions']);

        // 发送系统消息
        Route::post('/send', [MessageSystemController::class, 'send']);

        // 系统消息列表
        Route::get('/list', [MessageSystemController::class, 'list']);
    });
});
