<?php

use App\Http\Controllers\Detection\DetectionTaskController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('system.auth')->prefix('detection')->group(function () {
    // 监测任务模块
    Route::prefix('task')->group(function () {
        // 列表
        Route::get('/list', [DetectionTaskController::class, 'index']);
        // 详情
        Route::get('/item/{taskId}', [DetectionTaskController::class, 'show'])
            ->where('taskId', '[0-9]+');
        // 新增
        Route::post('/create', [DetectionTaskController::class, 'store']);
        // 修改
        Route::put('/update', [DetectionTaskController::class, 'update']);
        // 任务开关
        Route::put('/switch', [DetectionTaskController::class, 'taskSwitch']);
        // 删除
        Route::delete('/delete/{taskId}', [DetectionTaskController::class, 'destroy'])
            ->where('taskId', '[0-9]+');
    });
});
