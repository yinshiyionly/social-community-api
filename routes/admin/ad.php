<?php

use App\Http\Controllers\Admin\AdItemController;
use App\Http\Controllers\Admin\AdSpaceController;
use App\Http\Controllers\App\AdController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin 广告模块路由
|--------------------------------------------------------------------------
*/

Route::prefix('ad')->group(function () {
    // 轮播图列表
    Route::get('list', [AdController::class, 'list']);

    // 广告位管理
    Route::prefix('space')->group(function () {
        Route::get('list', [AdSpaceController::class, 'list']);
        Route::get('optionselect', [AdSpaceController::class, 'optionselect']);
        Route::get('{spaceId}', [AdSpaceController::class, 'show']);
        Route::post('', [AdSpaceController::class, 'store']);
        Route::put('', [AdSpaceController::class, 'update']);
        Route::put('changeStatus', [AdSpaceController::class, 'changeStatus']);
        Route::delete('{spaceIds}', [AdSpaceController::class, 'destroy']);
    });

    // 广告内容管理
    Route::prefix('item')->group(function () {
        Route::get('list', [AdItemController::class, 'list']);
        Route::get('{adId}', [AdItemController::class, 'show']);
        Route::post('', [AdItemController::class, 'store']);
        Route::put('', [AdItemController::class, 'update']);
        Route::put('changeStatus', [AdItemController::class, 'changeStatus']);
        Route::delete('{adIds}', [AdItemController::class, 'destroy']);
    });
});
