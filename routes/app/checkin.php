<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\App\CheckinController;

/*
|--------------------------------------------------------------------------
| 签到相关路由
|--------------------------------------------------------------------------
*/

Route::prefix('v1/checkin')->middleware('app.jwt.auth')->group(function () {
    // 执行签到
    Route::post('', [CheckinController::class, 'checkin']);

    // 获取签到状态
    Route::get('status', [CheckinController::class, 'status']);

    // 获取签到奖励配置列表（7天奖励展示）
    Route::get('rewards', [CheckinController::class, 'rewardList']);

    // 获取月度签到记录（日历展示）
    Route::get('monthly', [CheckinController::class, 'monthlyRecords']);
});
