<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\CourseCategoryController;

/*
|--------------------------------------------------------------------------
| Admin Course Routes
|--------------------------------------------------------------------------
|
| 课程模块管理路由
|
*/

Route::middleware('system.auth')->group(function () {

    // 课程分类管理
    Route::prefix('admin/course/category')->group(function () {
        // 列表
        Route::get('/list', [CourseCategoryController::class, 'list']);
        // 树形列表
        Route::get('/treeList', [CourseCategoryController::class, 'treeList']);
        // 下拉框列表
        Route::get('/optionselect', [CourseCategoryController::class, 'optionselect']);
        // 详情
        Route::get('/{categoryId}', [CourseCategoryController::class, 'show'])->where('categoryId', '[0-9]+');
        // 创建
        Route::post('/', [CourseCategoryController::class, 'store']);
        // 更新
        Route::put('/', [CourseCategoryController::class, 'update']);
        // 更改状态
        Route::put('/changeStatus', [CourseCategoryController::class, 'changeStatus']);
        // 删除
        Route::delete('/{categoryIds}', [CourseCategoryController::class, 'destroy']);
    });

});
