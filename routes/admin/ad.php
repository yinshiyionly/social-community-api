<?php

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
});
