<?php

use App\Http\Controllers\Admin\MemberController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Member Routes
|--------------------------------------------------------------------------
|
| 后台会员管理路由
|
*/

Route::middleware('system.auth')->group(function () {
    Route::prefix('member')->group(function () {
        // 用户列表
        Route::get('/list', [MemberController::class, 'list']);

        // 新增官方会员账号
        Route::post('/official', [MemberController::class, 'storeOfficial']);

        // 更新官方会员账号
        Route::put('/official', [MemberController::class, 'updateOfficial']);
    });
});
