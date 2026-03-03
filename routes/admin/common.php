<?php

use App\Http\Controllers\Admin\CommonController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Common Routes
|--------------------------------------------------------------------------
|
| 后台通用模块路由
|
*/

Route::middleware('system.auth')->prefix('/common')->group(function () {
    Route::post('uploadImage', [CommonController::class, 'uploadImage']);
    Route::post('uploadImages', [CommonController::class, 'uploadImages']);
    Route::post('uploadVideo', [CommonController::class, 'uploadVideo']);
});
