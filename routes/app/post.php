<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\App\PostController;

/*
|--------------------------------------------------------------------------
| 动态相关路由
|--------------------------------------------------------------------------
*/

Route::prefix('post')->group(function () {
    // 动态列表（游标分页）
    Route::get('list', [PostController::class, 'list']);
});
