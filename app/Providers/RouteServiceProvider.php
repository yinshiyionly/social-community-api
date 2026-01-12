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

            // Admin 后台管理系统路由 (api/*)
            Route::prefix('api')
                ->group(base_path('routes/admin.php'));

            // App 端路由 (app/*)
            Route::prefix('app')
                ->middleware('api')
                ->group(base_path('routes/app.php'));

            // 文件上传模块路由
            Route::prefix('api')
                ->group(base_path('routes/file.php'));
        });
    }
}
