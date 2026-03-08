<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    'dashboard' => [
        'port' => env('WEBSOCKETS_DASHBOARD_PORT', 6001),
    ],

    /*
    |--------------------------------------------------------------------------
    | Applications Repository
    |--------------------------------------------------------------------------
    */

    'apps' => [
        [
            'id' => env('PUSHER_APP_ID'),
            'name' => env('APP_NAME'),
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'path' => env('PUSHER_APP_PATH'),
            'capacity' => null,
            'enable_client_messages' => false,
            // 关掉统计上报配置
            'enable_statistics' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | App Provider
    |--------------------------------------------------------------------------
    */

    'app_provider' => BeyondCode\LaravelWebSockets\Apps\ConfigAppProvider::class,

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins
    |--------------------------------------------------------------------------
    */

    'allowed_origins' => [
        '*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Max Request Size In Kilobytes
    |--------------------------------------------------------------------------
    */

    'max_request_size_in_kb' => 250,

    /*
    |--------------------------------------------------------------------------
    | WebSocket Path
    |--------------------------------------------------------------------------
    */

    'path' => env('WEBSOCKETS_PATH', 'app'),

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    */

    'middleware' => [
        'web',
    ],

    /*
    |--------------------------------------------------------------------------
    | Statistics
    |--------------------------------------------------------------------------
    */

    'statistics' => [
        'model' => \BeyondCode\LaravelWebSockets\Statistics\Models\WebSocketsStatisticsEntry::class,
        'logger' => \BeyondCode\LaravelWebSockets\Statistics\Logger\HttpStatisticsLogger::class,
        'interval_in_seconds' => 60,
        'delete_statistics_older_than_days' => 60,
        'perform_dns_lookup' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | SSL Configuration
    |--------------------------------------------------------------------------
    */

    'ssl' => [
        'local_cert' => env('LARAVEL_WEBSOCKETS_SSL_LOCAL_CERT', null),
        'local_pk' => env('LARAVEL_WEBSOCKETS_SSL_LOCAL_PK', null),
        'passphrase' => env('LARAVEL_WEBSOCKETS_SSL_PASSPHRASE', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Channel Manager
    |--------------------------------------------------------------------------
    */

    'channel_manager' => \BeyondCode\LaravelWebSockets\WebSockets\Channels\LocalChannelManager::class,

];
