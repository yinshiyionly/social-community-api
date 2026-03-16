<?php

use App\Http\Controllers\App\SuXueDaoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 速学岛AI工具项目入口路由
|--------------------------------------------------------------------------
*/

Route::prefix('v1/suxuedao')->middleware('app.auth')->group(function () {
    // 获取速学岛AI工具项目的Authorization
    Route::get('getAuthorization', [SuXueDaoController::class, 'getAuthorization']);
});
