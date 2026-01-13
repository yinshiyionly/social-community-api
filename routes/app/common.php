<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\App\CommonController;

/*
|--------------------------------------------------------------------------
| App Common Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1/common')->middleware(['app.auth'])->group(function () {
    // 上传单张图片
    Route::post('uploadImage', [CommonController::class, 'uploadImage']);
    // 上传多张图片
    Route::post('uploadImages', [CommonController::class, 'uploadImages']);
});
