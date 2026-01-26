<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\App\MemberAuthController;

/*
|--------------------------------------------------------------------------
| 会员相关路由
|--------------------------------------------------------------------------
*/

Route::prefix('v1/member')->group(function () {
    // 手机号密码登录
    Route::post('login', [MemberAuthController::class, 'login']);

    // 手机号验证码登录
    Route::post('login/sms', [MemberAuthController::class, 'smsLogin']);

    // 微信 APP 登录
    Route::post('login/wechat', [MemberAuthController::class, 'appWeChatLogin']);

    // 发送登录验证码
    Route::post('sms/send', [MemberAuthController::class, 'sendSms']);
});
