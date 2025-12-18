<?php

use App\Http\Controllers\PublicRelation\MaterialDefamationController;
use App\Http\Controllers\PublicRelation\MaterialEnterpriseController;
use App\Http\Controllers\PublicRelation\MaterialPoliticsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('system.auth')->prefix('material')->group(function () {
    // 公关维权-资料管理-企业类资料
    Route::prefix('enterprise')->group(function () {
        // 列表
        Route::get('/list', [MaterialEnterpriseController::class, 'index']);
        // 详情
        Route::get('/item/{id}', [MaterialEnterpriseController::class, 'show'])
            ->where('id', '[0-9]+');
        // 新增
        Route::post('/create', [MaterialEnterpriseController::class, 'store']);
        // 修改
        Route::put('/update', [MaterialEnterpriseController::class, 'update']);
        // 删除
        Route::delete('/delete/{id}', [MaterialEnterpriseController::class, 'destroy'])
            ->where('id', '[0-9]+');
        // 获取举报人实体列表
        Route::get('/report_entity', [MaterialEnterpriseController::class, 'getReportEntityList']);
    });

    // 公关维权-资料管理-政治类资料
    Route::prefix('politics')->group(function () {
        // 列表
        Route::get('/list', [MaterialPoliticsController::class, 'index']);
        // 详情
        Route::get('/item/{id}', [MaterialPoliticsController::class, 'show'])
            ->where('id', '[0-9]+');
        // 新增
        Route::post('/create', [MaterialPoliticsController::class, 'store']);
        // 更新
        Route::put('/update', [MaterialPoliticsController::class, 'update']);
        // 删除
        Route::delete('/delete/{id}', [MaterialPoliticsController::class, 'destroy'])
            ->where('id', '[0-9]+');
        // 获取举报人实体列表
        Route::get('/report_entity', [MaterialPoliticsController::class, 'getReportEntityList']);
    });

    // 公关维权-资料管理-诽谤类资料
    Route::prefix('defamation')->group(function () {
        // 列表
        Route::get('/list', [MaterialDefamationController::class, 'index']);
        // 详情
        Route::get('/item/{id}', [MaterialDefamationController::class, 'show'])
            ->where('id', '[0-9]+');
        // 新增
        Route::post('/create', [MaterialDefamationController::class, 'store']);
        // 更新
        Route::put('/update', [MaterialDefamationController::class, 'update']);
        // 删除
        Route::delete('/delete/{id}', [MaterialDefamationController::class, 'destroy'])
            ->where('id', '[0-9]+');
        // 获取举报人实体列表
        Route::get('/report_entity', [MaterialDefamationController::class, 'getReportEntityList']);
    });
});
