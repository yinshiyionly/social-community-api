<?php

use App\Http\Controllers\App\CourseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| App 课程相关路由
|--------------------------------------------------------------------------
*/

Route::prefix('app/course')->group(function () {
    // 课程分类列表
    Route::get('/categories', [CourseController::class, 'categories']);

    // 选课中心 - 按分类获取课程列表
    Route::get('/list', [CourseController::class, 'listByCategory']);

    // 课程详情（可选登录，登录后返回是否已拥有）
    Route::get('/detail', [CourseController::class, 'detail'])->middleware('app.jwt.optional');

    // 好课上新列表
    Route::get('/new', [CourseController::class, 'newCourses']);

    // 名师好课列表
    Route::get('/recommend', [CourseController::class, 'recommendCourses']);

    // 免费领取课程（需登录）
    Route::post('/claim', [CourseController::class, 'claim'])->middleware('app.jwt');

    // 购买课程（需登录）
    Route::post('/purchase', [CourseController::class, 'purchase'])->middleware('app.jwt');
});
