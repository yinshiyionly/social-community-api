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

    // 搜索全部（用户+课程混合，支持可选登录）
    Route::get('all', [SearchController::class, 'searchAll'])->middleware('app.auth.optional');

    // 搜索用户（支持可选登录）
    Route::get('user', [SearchController::class, 'searchUser'])->middleware('app.auth.optional');
});
