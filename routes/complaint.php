<?php

use App\Http\Controllers\PublicRelation\ComplaintDefamationController;
use App\Http\Controllers\PublicRelation\ComplaintEnterpriseController;
use App\Http\Controllers\PublicRelation\ComplaintPoliticsController;
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
    });
});
