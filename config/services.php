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
    | 微信支付配置
    |--------------------------------------------------------------------------
    */
    'wechat_pay' => [
        // APP 支付使用的 appid，默认复用微信登录 appid
        'app_id' => env('WECHAT_PAY_APP_ID', env('WECHAT_APP_APPID')),
        'mch_id' => env('WECHAT_PAY_MCH_ID'),
        // 微信支付 v2 API Key（当前支付主链路使用）
        'mch_secret_key_v2' => env('WECHAT_PAY_MCH_SECRET_KEY_V2'),
        // 微信支付 v2 签名类型：MD5 / HMAC-SHA256
        'sign_type' => env('WECHAT_PAY_SIGN_TYPE', 'MD5'),
        // 微信支付 v2 API 地址
        'api_base_v2' => env('WECHAT_PAY_API_BASE_V2', 'https://api.mch.weixin.qq.com'),
        // 微信支付 v2 退款证书（优先读 v2 变量，未配置时回退旧变量）
        'mch_secret_cert_v2' => env('WECHAT_PAY_MCH_SECRET_CERT_V2', env('WECHAT_PAY_MCH_SECRET_CERT')),
        'mch_public_cert_path_v2' => env('WECHAT_PAY_MCH_PUBLIC_CERT_PATH_V2', env('WECHAT_PAY_MCH_PUBLIC_CERT_PATH')),

        // 以下为历史 v3 配置，当前支付主链路不依赖
        'mch_secret_key' => env('WECHAT_PAY_MCH_SECRET_KEY'),
        'mch_secret_cert' => env('WECHAT_PAY_MCH_SECRET_CERT'),
        'mch_public_cert_path' => env('WECHAT_PAY_MCH_PUBLIC_CERT_PATH'),
        // 若未显式配置，默认使用 APP_URL + 固定回调路径
        'notify_url' => env(
            'WECHAT_PAY_NOTIFY_URL',
            rtrim(env('APP_URL', ''), '/') . '/api/app/v1/course/pay/wechat/notify'
        ),
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
            // 短信模版-注册
            'template_login' => env('VOLCENGINE_SMS_SIGN_TEMPLATE_LOGIN'),
            // 短信模版-绑定手机号(复用注册)
            'template_bind_phone' => env('VOLCENGINE_SMS_SIGN_TEMPLATE_LOGIN')
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
        'base_url' => env('BAIJIAYUN_BASE_URL', ''),
        'private_domain' => env('BAIJIAYUN_PRIVATE_DOMAIN', '')
    ],
];
