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

Route::middleware('system.auth')->group(function () {

    // 直播间管理
    Route::prefix('admin/live/room')->group(function () {
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
        // 删除
        Route::delete('/{roomIds}', [LiveRoomController::class, 'destroy']);
    });

});
