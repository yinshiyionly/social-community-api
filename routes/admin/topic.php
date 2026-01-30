<?php

use App\Http\Controllers\Admin\TopicController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Topic Routes
|--------------------------------------------------------------------------
|
| 后台话题管理相关路由
|
*/

// 需要认证的路由
Route::middleware('system.auth')->group(function () {
    Route::prefix('admin/topic')->group(function () {
        // 话题列表
        Route::get('/list', [TopicController::class, 'list']);

        // 下拉选项（无需权限控制，供其他模块使用）
        Route::get('/optionselect', [TopicController::class, 'optionselect']);

        // 话题详情
        Route::get('/{topicId}', [TopicController::class, 'show']);

        // 新增话题
        Route::post('/', [TopicController::class, 'store']);

        // 更新话题
        Route::put('/', [TopicController::class, 'update']);

        // 删除话题（支持批量）
        Route::delete('/{topicIds}', [TopicController::class, 'destroy']);

        // 修改状态
        Route::put('/changeStatus', [TopicController::class, 'changeStatus']);

        // 设置推荐
        Route::put('/changeRecommend', [TopicController::class, 'changeRecommend']);
    });
});
