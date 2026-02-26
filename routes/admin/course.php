<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\CourseCategoryController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\VideoChapterController;
use App\Http\Controllers\Admin\LiveChapterController;

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

    // 课程管理
    Route::prefix('admin/course')->group(function () {
        // 列表
        Route::get('/list', [CourseController::class, 'list']);
        // 下拉框列表
        Route::get('/optionselect', [CourseController::class, 'optionselect']);
        // 详情
        Route::get('/{courseId}', [CourseController::class, 'show'])->where('courseId', '[0-9]+');
        // 创建
        Route::post('/', [CourseController::class, 'store']);
        // 更新
        Route::put('/', [CourseController::class, 'update']);
        // 更改状态
        Route::put('/changeStatus', [CourseController::class, 'changeStatus']);
        // 删除
        Route::delete('/{courseIds}', [CourseController::class, 'destroy']);
    });

    // 录播课章节管理
    Route::prefix('admin/course/video/chapter')->group(function () {
        // 章节列表（分页）
        Route::get('/list/{courseId}', [VideoChapterController::class, 'list'])->where('courseId', '[0-9]+');
        // 章节列表（全部，用于排序）
        Route::get('/all/{courseId}', [VideoChapterController::class, 'all'])->where('courseId', '[0-9]+');
        // 章节详情
        Route::get('/{chapterId}', [VideoChapterController::class, 'show'])->where('chapterId', '[0-9]+');
        // 创建章节
        Route::post('/', [VideoChapterController::class, 'store']);
        // 更新章节
        Route::put('/', [VideoChapterController::class, 'update']);
        // 更改状态
        Route::put('/changeStatus', [VideoChapterController::class, 'changeStatus']);
        // 批量排序
        Route::put('/batchSort', [VideoChapterController::class, 'batchSort']);
        // 删除章节
        Route::delete('/{chapterIds}', [VideoChapterController::class, 'destroy']);
    });

    // 直播课章节管理
    Route::prefix('admin/course/live/chapter')->group(function () {
        // 章节列表（分页）
        Route::get('/list/{courseId}', [LiveChapterController::class, 'list'])->where('courseId', '[0-9]+');
        // 章节详情
        Route::get('/{chapterId}', [LiveChapterController::class, 'show'])->where('chapterId', '[0-9]+');
        // 创建章节
        Route::post('/', [LiveChapterController::class, 'store']);
        // 更新章节
        Route::put('/', [LiveChapterController::class, 'update']);
        // 更改状态
        Route::put('/changeStatus', [LiveChapterController::class, 'changeStatus']);
        // 同步直播间状态
        Route::put('/syncLiveStatus/{chapterId}', [LiveChapterController::class, 'syncLiveStatus'])->where('chapterId', '[0-9]+');
        // 获取回放列表
        Route::get('/playback/{chapterId}', [LiveChapterController::class, 'playbackList'])->where('chapterId', '[0-9]+');
        // 同步回放信息
        Route::put('/syncPlayback/{chapterId}', [LiveChapterController::class, 'syncPlayback'])->where('chapterId', '[0-9]+');
        // 删除章节
        Route::delete('/{chapterIds}', [LiveChapterController::class, 'destroy']);
    });

});
