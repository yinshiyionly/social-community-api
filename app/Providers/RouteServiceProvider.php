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
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        // $this->configureRateLimiting();
        // $this->configureModelBindings();

        $this->routes(function () {
            // 测试路由
            Route::prefix('test')
                ->group(base_path('routes/web.php'));

            // App 端路由 api/app/v1/*
            Route::prefix('api/app/v1')
                ->middleware('api')
                ->group(base_path('routes/app.php'));

            // Admin 后台管理系统路由 api/admin/*
            Route::prefix('api/admin')
                ->group(base_path('routes/admin.php'));

            // 文件上传模块路由
            Route::prefix('api')
                ->group(base_path('routes/file.php'));
        });
    }
}
