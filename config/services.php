<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | 微信服务配置
    |--------------------------------------------------------------------------
    */
    'wechat' => [
        // 移动应用（APP）
        'app' => [
            'app_id' => env('WECHAT_APP_APPID'),
            'app_secret' => env('WECHAT_APP_SECRET'),
        ],
        // 小程序（可选，后续扩展）
        'mini_program' => [
            'app_id' => env('WECHAT_MINI_APPID'),
            'app_secret' => env('WECHAT_MINI_SECRET'),
        ],
        // 公众号/H5（可选，后续扩展）
        'official_account' => [
            'app_id' => env('WECHAT_OA_APPID'),
            'app_secret' => env('WECHAT_OA_SECRET'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 火山云短信服务配置
    |--------------------------------------------------------------------------
    */
    'volcengine' => [
        'sms' => [
            'access_key' => env('VOLCENGINE_SMS_ACCESS_KEY'),
            'secret_key' => env('VOLCENGINE_SMS_SECRET_KEY'),
            'region' => env('VOLCENGINE_SMS_REGION', 'cn-north-1'),
            'endpoint' => env('VOLCENGINE_SMS_ENDPOINT', 'sms.volcengineapi.com'),
            'sign_name' => env('VOLCENGINE_SMS_SIGN_NAME'),
            'account' => env('VOLCENGINE_SMS_ACCOUNT'),
            'template_login' => env('VOLCENGINE_SMS_SIGN_TEMPLATE_LOGIN')
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | 百家云直播服务配置
    |--------------------------------------------------------------------------
    */
    'baijiayun' => [
        'partner_id' => env('BAIJIAYUN_PARTNER_ID', ''),
        'partner_key' => env('BAIJIAYUN_PARTNER_KEY', ''),
        'base_url' => env('BAIJIAYUN_BASE_URL', 'https://api.baijiayun.com'),
        'default_room_type' => env('BAIJIAYUN_DEFAULT_ROOM_TYPE', 2),
    ],
];
