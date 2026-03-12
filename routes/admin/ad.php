<?php

use App\Http\Controllers\Admin\AdController;
use App\Http\Controllers\Admin\AdItemController;
use App\Http\Controllers\Admin\AdSpaceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin 广告模块路由
|--------------------------------------------------------------------------
|
| 设计约束：
| 1. 所有接口都要求后台鉴权，避免误暴露广告配置能力；
| 2. 广告位与广告内容拆分前缀，便于后续按模块配置权限。
|
*/

Route::middleware('system.auth')->group(function () {
    Route::prefix('/ad')->group(function () {
        // 轮播广告预览列表
        Route::get('/list', [AdController::class, 'list']);
        // 广告模块常量选项
        Route::get('/constants', [AdController::class, 'constants']);

        // 广告位管理
        Route::prefix('/space')->group(function () {
            // 列表
            Route::get('/list', [AdSpaceController::class, 'list']);
            // 下拉列表
            Route::get('/optionselect', [AdSpaceController::class, 'optionselect']);
            // 详情
            Route::get('/{spaceId}', [AdSpaceController::class, 'show'])->where('spaceId', '[0-9]+');
            // 创建
            Route::post('/', [AdSpaceController::class, 'store']);
            // 更新
            Route::put('/', [AdSpaceController::class, 'update']);
            // 更新状态
            Route::put('/changeStatus', [AdSpaceController::class, 'changeStatus']);
            // 删除
            Route::delete('/{spaceId}', [AdSpaceController::class, 'destroy'])->where('spaceId', '[0-9]+');
        });

        // 广告内容管理
        Route::prefix('/item')->group(function () {
            // 列表
            Route::get('/list', [AdItemController::class, 'list']);
            // 批量排序
            Route::put('/batchSort', [AdItemController::class, 'batchSort']);
            // 更新状态
            Route::put('/changeStatus', [AdItemController::class, 'changeStatus']);
            // 详情
            Route::get('/{adId}', [AdItemController::class, 'show'])->where('adId', '[0-9]+');
            // 创建
            Route::post('/', [AdItemController::class, 'store']);
            // 更新
            Route::put('/', [AdItemController::class, 'update']);
            // 删除
            Route::delete('/{adId}', [AdItemController::class, 'destroy'])->where('adId', '[0-9]+');
        });
    });
});
