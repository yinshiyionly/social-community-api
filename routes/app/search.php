<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\App\SearchController;

/*
|--------------------------------------------------------------------------
| 搜索相关路由
|--------------------------------------------------------------------------
*/

Route::prefix('v1/search')->group(function () {
    // 搜索接口（公开）
    Route::get('', [SearchController::class, 'search']);
});
