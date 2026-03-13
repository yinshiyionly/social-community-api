<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Policy & Protocol Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for policy and protocol pages.
| These routes are loaded by the RouteServiceProvider within a group which
| is assigned the "web" middleware group or no middleware by default, 
| depending on your configuration.
|
*/

// 用户服务协议
Route::view('/user-service', 'policy-protocol.user-service');

// 隐私政策
Route::view('/privacy', 'policy-protocol.privacy');

// 购买课程协议
Route::view('/purchase-courses', 'policy-protocol.purchase-courses');

// 个人信息授权与保护声明
Route::view('/personal-information', 'policy-protocol.personal-information-authorization-and-protection-statement');
