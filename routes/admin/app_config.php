<?php

use App\Http\Controllers\Admin\AppConfigController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin App Config Routes
|--------------------------------------------------------------------------
|
| App 配置管理路由。
|
| 路由约束：
| 1. 统一走 system.auth 鉴权，避免未授权修改运行时配置；
| 2. 使用 /app/config 前缀，与 system/config 分离，避免语义混淆。
|
*/

Route::middleware('system.auth')->group(function () {
    Route::prefix('/app/config')->group(function () {
        // 列表
        Route::get('/list', [AppConfigController::class, 'list']);
        // 详情
        Route::get('/{configId}', [AppConfigController::class, 'show'])
            ->where('configId', '[0-9]+');
        // 创建
        Route::post('/', [AppConfigController::class, 'store']);
        // 更新
        Route::put('/', [AppConfigController::class, 'update']);
        // 修改启用状态
        Route::put('/changeStatus', [AppConfigController::class, 'changeStatus']);
        // 删除（仅单条）
        Route::delete('/{configId}', [AppConfigController::class, 'destroy'])
            ->where('configId', '[0-9]+');
    });
});
