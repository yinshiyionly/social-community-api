<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\LiveRoomController;

/*
|--------------------------------------------------------------------------
| Admin Live Routes
|--------------------------------------------------------------------------
|
| 直播间管理路由
|
*/

/**
 * 不需要鉴权的接口
 */
// 处理直播上下课回调
Route::post('live/classCallback', [LiveRoomController::class, 'classCallback']);

/**
 * 需要鉴权的接口
 */
Route::middleware('system.auth')->group(function () {

    // 直播间管理
    Route::prefix('/live/room')->group(function () {
        // 直播间常量配置项
        Route::get('/constants', [LiveRoomController::class, 'constants']);
        // 伪直播选择点播视频列表
        Route::get('/mockVideoList', [LiveRoomController::class, 'mockVideoList']);
        // 伪直播选择回放列表
        Route::get('/mockPlaybackList', [LiveRoomController::class, 'mockPlaybackList']);
        // 列表
        Route::get('/list', [LiveRoomController::class, 'list']);
        // 详情
        Route::get('/{roomId}', [LiveRoomController::class, 'show'])->where('roomId', '[0-9]+');
        // 创建
        Route::post('/', [LiveRoomController::class, 'store']);
        // 更新
        Route::put('/', [LiveRoomController::class, 'update']);
        // 更改状态
        Route::put('/changeStatus', [LiveRoomController::class, 'changeStatus']);
        // 发送红包
        Route::post('/redPacket/send', [LiveRoomController::class, 'sendRedPacket']);
        // 删除-不支持批量删除
        Route::delete('/{roomId}', [LiveRoomController::class, 'destroy'])
            ->where('roomId', '[0-9]+');
    });

});
