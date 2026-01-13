<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\App\MemberAuthController;

/*
|--------------------------------------------------------------------------
| 会员相关路由
|--------------------------------------------------------------------------
*/

Route::prefix('v1/member')->group(function () {
    // 登录（无需认证）
    Route::post('login', [MemberAuthController::class, 'login']);

    // 发送登录验证码
    Route::post('sms/send', [MemberAuthController::class, 'sendSms']);
});
