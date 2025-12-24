<?php

use App\Http\Controllers\Mail\ReportEmailController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('system.auth')->prefix('mail')->group(function () {
    // 邮箱管理
    // 列表
    Route::get('/list', [ReportEmailController::class, 'index']);
    // 详情
    Route::get('/item/{id}', [ReportEmailController::class, 'show'])
        ->where('id', '[0-9]+');
    // 新增
    Route::post('/create', [ReportEmailController::class, 'store']);
    // 修改
    Route::put('/update', [ReportEmailController::class, 'update']);
    // 删除
    Route::delete('/delete/{id}', [ReportEmailController::class, 'destroy'])
        ->where('id', '[0-9]+');
    // 获取通用不分页列表
    Route::get('/commonList', [ReportEmailController::class, 'commonList']);
    // 发送测试邮箱
    Route::post('/sendTest', [ReportEmailController::class, 'sendTest']);
});
