<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\App\MemberAuthController;
use App\Http\Controllers\App\MemberController;

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

    Route::middleware('app.auth')->group(function () {
        // 用户帖子列表
        Route::get('post', [MemberController::class, 'posts']);
        // 个人收藏帖子列表
        Route::get('collections', [MemberController::class, 'collections']);
        // 个人粉丝列表
        Route::get('fans', [MemberController::class, 'fans']);
        // 个人关注列表
        Route::get('followings', [MemberController::class, 'followings']);
        // 修改头像
        Route::put('avatar', [MemberController::class, 'updateAvatar']);
        // 修改昵称
        Route::put('nickname', [MemberController::class, 'updateNickname']);
    });

    // 用户主页详情（可选鉴权）
    Route::get('{id}/profile', [MemberController::class, 'profile'])
        ->middleware('app.jwt.optional')
        ->where('id', '[0-9]+');


});
