<?php

use App\Http\Controllers\App\CourseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| App 课程相关路由
|--------------------------------------------------------------------------
*/

Route::prefix('app/course')->group(function () {
    // 好课上新列表
    Route::get('/new', [CourseController::class, 'newCourses']);
});
