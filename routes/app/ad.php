<?php

use App\Http\Controllers\App\AdController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| App 广告模块路由
|--------------------------------------------------------------------------
*/

Route::prefix('ad')->group(function () {
    // 广告列表
    Route::get('list', [AdController::class, 'list']);
});
