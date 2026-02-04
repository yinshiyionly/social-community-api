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
        Route::get('/list', [CourseCategoryController::class, 'list']);
        Route::get('/treeList', [CourseCategoryController::class, 'treeList']);
        Route::get('/optionselect', [CourseCategoryController::class, 'optionselect']);
        Route::get('/{categoryId}', [CourseCategoryController::class, 'show'])->where('categoryId', '[0-9]+');
        Route::post('/', [CourseCategoryController::class, 'store']);
        Route::put('/', [CourseCategoryController::class, 'update']);
        Route::put('/changeStatus', [CourseCategoryController::class, 'changeStatus']);
        Route::delete('/{categoryIds}', [CourseCategoryController::class, 'destroy']);
    });

});
