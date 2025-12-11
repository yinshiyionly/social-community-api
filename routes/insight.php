<?php

use App\Http\Controllers\Insight\InsightSyncController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Insight API Routes
|--------------------------------------------------------------------------
|
| 舆情数据同步相关路由
| 这些路由使用独立的 Token 认证，不走 system.auth 中间件
|
*/

// 舆情数据同步接口（独立认证，不走 system.auth）
Route::post('/insight/sync', [InsightSyncController::class, 'sync'])
    ->middleware('insight.sync.auth');
