<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\App\TopicController;

/*
|--------------------------------------------------------------------------
| App Topic Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1/topic')->group(function () {
    // 热门话题列表（发帖选择用）- 无需登录
    Route::get('hotList', [TopicController::class, 'hotList']);
});
