<?php

use App\Http\Controllers\Complaint\ComplaintDefamationController;
use App\Http\Controllers\Complaint\ComplaintEnterpriseController;
use App\Http\Controllers\Complaint\ComplaintPoliticsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('system.auth')->prefix('complaint')->group(function () {
    // 公关维权-我要投诉-企业类
    Route::prefix('enterprise')->group(function () {
        // 列表
        Route::get('/list', [ComplaintEnterpriseController::class, 'index']);
        // 详情
        Route::get('/item/{id}', [ComplaintEnterpriseController::class, 'show'])
            ->where('id', '[0-9]+');
        // 新增
        Route::post('/create', [ComplaintEnterpriseController::class, 'store']);
        // 修改
        Route::put('/update', [ComplaintEnterpriseController::class, 'update']);
        // 删除
        Route::delete('/delete/{id}', [ComplaintEnterpriseController::class, 'destroy'])
            ->where('id', '[0-9]+');
        // 获取证据种类枚举列表
        Route::get('/proof-types', [ComplaintEnterpriseController::class, 'getProofTypes']);
        // 获取可用发件邮箱列表
        Route::get('/report-emails', [ComplaintEnterpriseController::class, 'getReportEmails']);
        // 发送举报邮件
        Route::post('/sendMail', [ComplaintEnterpriseController::class, 'sendMail']);
    });

    // 公关维权-我要投诉-政治类
    Route::prefix('politics')->group(function () {
        // 列表
        Route::get('/list', [ComplaintPoliticsController::class, 'index']);
        // 详情
        Route::get('/item/{id}', [ComplaintPoliticsController::class, 'show'])
            ->where('id', '[0-9]+');
        // 新增
        Route::post('/create', [ComplaintPoliticsController::class, 'store']);
        // 更新
        Route::put('/update', [ComplaintPoliticsController::class, 'update']);
        // 删除
        Route::delete('/delete/{id}', [ComplaintPoliticsController::class, 'destroy'])
            ->where('id', '[0-9]+');
        // 获取危害小类枚举列表
        Route::get('/report-sub-types', [ComplaintPoliticsController::class, 'getReportSubTypes']);
        // 获取被举报平台枚举列表
        Route::get('/report-platforms', [ComplaintPoliticsController::class, 'getReportPlatforms']);
        // 获取APP定位枚举列表
        Route::get('/app-locations', [ComplaintPoliticsController::class, 'getAppLocations']);
        // 获取账号平台枚举列表
        Route::get('/account-platforms', [ComplaintPoliticsController::class, 'getAccountPlatforms']);
        // 根据账号平台获取账号性质枚举列表
        Route::get('/account-natures/{platform}', [ComplaintPoliticsController::class, 'getAccountNatures']);
        // 获取可用发件邮箱列表
        Route::get('/report-emails', [ComplaintPoliticsController::class, 'getReportEmails']);
        // 发送举报邮件
        Route::post('sendMail', [ComplaintPoliticsController::class, 'sendMail']);
    });

    // 公关维权-我要投诉-诽谤类
    Route::prefix('defamation')->group(function () {
        // 列表
        Route::get('/list', [ComplaintDefamationController::class, 'index']);
        // 详情
        Route::get('/item/{id}', [ComplaintDefamationController::class, 'show'])
            ->where('id', '[0-9]+');
        // 新增
        Route::post('/create', [ComplaintDefamationController::class, 'store']);
        // 更新
        Route::put('/update', [ComplaintDefamationController::class, 'update']);
        // 删除
        Route::delete('/delete/{id}', [ComplaintDefamationController::class, 'destroy'])
            ->where('id', '[0-9]+');
        // 获取可用发件邮箱列表
        Route::get('/report-emails', [ComplaintDefamationController::class, 'getReportEmails']);
        // 发送举报邮件
        Route::post('/sendMail', [ComplaintDefamationController::class, 'sendMail']);
    });
});
