<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    // protected $namespace = 'App\\Http\\Controllers';
    protected $systemNamespace = 'App\\Http\\Controllers\\System';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        // $this->configureRateLimiting();
        // $this->configureModelBindings();

        $this->routes(function () {
            Route::prefix('test')
                ->group(base_path('routes/web.php'));
            // 后台系统统一路由
            Route::prefix('api')
                ->group(base_path('routes/admin.php'));

            // 后台系统文件上传模块路由
            Route::prefix('api')
                ->group(base_path('routes/file.php'));

            // 公关维权-材料管理
            Route::prefix('api')
                ->group(base_path('routes/material.php'));

            // 舆情数据同步接口（独立认证）
            Route::prefix('api')
                ->group(base_path('routes/insight.php'));

            // 监测任务
            Route::prefix('api')
                ->group(base_path('routes/detection.php'));
        });
    }
}
