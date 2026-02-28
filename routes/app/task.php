<?php

use App\Http\Controllers\App\TaskController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| App 任务中心路由
|--------------------------------------------------------------------------
*/

Route::prefix('v1/task')->middleware('app.auth')->group(function () {
    // 任务中心数据
    Route::get('/center', [TaskController::class, 'center']);

    // 学分明细（分页）
    Route::get('/score/detail', [TaskController::class, 'scoreDetail']);
});
