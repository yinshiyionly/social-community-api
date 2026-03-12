<?php

use App\Http\Controllers\Admin\PostController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Post Routes
|--------------------------------------------------------------------------
|
| 后台帖子管理路由
|
*/

Route::middleware('system.auth')->group(function () {
    Route::prefix('post')->group(function () {
        // 帖子模块常量选项
        Route::get('/constants', [PostController::class, 'constants']);

        // 官方账号下拉选项（后台发帖选择发帖人）
        Route::get('/officialMemberOptionselect', [PostController::class, 'officialMemberOptionselect']);

        // 后台发帖 - 图文
        Route::post('/store/imageText', [PostController::class, 'storeImageText']);

        // 后台发帖 - 视频
        Route::post('/store/video', [PostController::class, 'storeVideo']);

        // 后台发帖 - 文章
        Route::post('/store/article', [PostController::class, 'storeArticle']);

        // 帖子列表
        Route::get('/list', [PostController::class, 'list']);

        // 帖子审核（通过/拒绝）
        Route::put('/audit', [PostController::class, 'audit']);

        // 帖子详情
        Route::get('/{postId}', [PostController::class, 'show'])
            ->where('postId', '[0-9]+');
    });
});
