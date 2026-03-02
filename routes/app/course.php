<?php

use App\Http\Controllers\App\CourseController;
use App\Http\Controllers\App\LearningCenterController;
use App\Http\Controllers\App\StudyCourseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| App 课程相关路由
|--------------------------------------------------------------------------
*/

Route::prefix('v1/course')->group(function () {
    // 课程分类列表
    Route::get('/categories', [CourseController::class, 'categories']);

    // 选课中心 - 按分类获取课程列表
    Route::get('/list', [CourseController::class, 'listByCategory']);

    // 课程详情（可选登录，登录后返回是否已拥有）
    // Route::get('/detail', [CourseController::class, 'detail'])->middleware('app.jwt.optional');
    Route::get('/detail', [CourseController::class, 'detail'])->middleware('app.auth.optional');

    // 好课上新列表
    Route::get('/new', [CourseController::class, 'newCourses']);

    // 名师好课列表
    Route::get('/recommend', [CourseController::class, 'recommendCourses']);

    // 大咖直播列表
    Route::get('/live', [CourseController::class, 'liveCourses']);

    // 免费领取课程（需登录）
    Route::post('/claim', [CourseController::class, 'claim'])->middleware('app.jwt');

    // 购买课程（需登录）
    Route::post('/purchase', [CourseController::class, 'purchase'])->middleware('app.jwt');
});


/*
|--------------------------------------------------------------------------
| App 学习中心路由
|--------------------------------------------------------------------------
*/

Route::prefix('app/learning')->middleware('app.jwt')->group(function () {
    // 我的课程列表
    Route::get('/courses', [LearningCenterController::class, 'myCourses']);

    // 课表日视图
    Route::get('/schedule', [LearningCenterController::class, 'dailySchedule']);

    // 课表周概览
    Route::get('/week', [LearningCenterController::class, 'weekOverview']);
});

/*
|--------------------------------------------------------------------------
| App 学习页（课表Tab）路由
|--------------------------------------------------------------------------
*/

Route::prefix('v1/study')->middleware('app.auth')->group(function () {
    // 课表区间数据（日期分组 + 日历红点）
    Route::get('/schedule/range', [LearningCenterController::class, 'scheduleRange']);
});

/*
|--------------------------------------------------------------------------
| App 学习页（课程Tab）路由
|--------------------------------------------------------------------------
*/

Route::prefix('v1/study/course')->group(function () {
    // 课程分类筛选项（无需登录）
    Route::get('/filters', [StudyCourseController::class, 'filters']);

    // 课程付费类型筛选项（无需登录）
    Route::get('/all-types', [StudyCourseController::class, 'allTypes']);

    // 以下接口需要登录
    Route::middleware('app.auth')->group(function () {
        // 今日学习任务
        Route::get('/today-tasks', [StudyCourseController::class, 'todayTasks']);

        // 学习页分组数据（最近学习 / 待学习 / 已结课）
        Route::get('/sections', [StudyCourseController::class, 'sections']);

        // 筛选后的课程列表
        Route::get('/list', [StudyCourseController::class, 'list']);
    });
});
