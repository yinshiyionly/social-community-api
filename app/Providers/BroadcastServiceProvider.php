<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // 直播 WS 当前只使用公开频道，不需要 broadcasting/auth 鉴权路由。

        require base_path('routes/channels.php');
    }
}
