<?php

use App\Http\Controllers\Admin\BaijiayunVideoController;
use App\Http\Controllers\Admin\SystemVideoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Video Routes
|--------------------------------------------------------------------------
|
| 百家云视频管理路由
|
*/

/**
 * 不需要鉴权的接口
 */
// 点播视频转码回调的地址
Route::post('/video/baijiayun/callback', [BaijiayunVideoController::class, 'callback']);

/**
 * 需要鉴权的接口
 */
Route::middleware('system.auth')->group(function () {
    Route::prefix('/video/baijiayun')->group(function () {
        // 常量选项（状态、发布状态、来源）
        Route::get('/constants', [BaijiayunVideoController::class, 'constants']);
        // 列表
        Route::get('/list', [BaijiayunVideoController::class, 'list']);
        // 详情
        Route::get('/{videoId}', [BaijiayunVideoController::class, 'show'])
            ->where('videoId', '[0-9]+');
        // 上传
        Route::post('/upload', [BaijiayunVideoController::class, 'uploadVideo']);
        // 创建
        Route::post('/', [BaijiayunVideoController::class, 'store']);
        // 更新
        Route::put('/', [BaijiayunVideoController::class, 'update']);
        // 删除（仅支持单个）
        Route::delete('/{videoId}', [BaijiayunVideoController::class, 'destroy'])
            ->where('videoId', '[0-9]+');
    });

    Route::prefix('/video/system')->group(function () {
        // 常量选项（状态、来源）
        Route::get('/constants', [SystemVideoController::class, 'constants']);
        // 列表
        Route::get('/list', [SystemVideoController::class, 'list']);
        // 详情
        Route::get('/{videoId}', [SystemVideoController::class, 'show'])
            ->where('videoId', '[0-9]+');
        // 创建
        Route::post('/', [SystemVideoController::class, 'store']);
        // 更新
        Route::put('/', [SystemVideoController::class, 'update']);
        // 删除-不支持批量
        Route::delete('/{videoId}', [SystemVideoController::class, 'destroy'])
            ->where('videoId', '[0-9]+');
    });
});
