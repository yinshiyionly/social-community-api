<?php

namespace App\Constant;

/**
 * Admin 后台 API 响应状态码和消息常量
 */
class AdminResponseCode
{
    // 成功
    const SUCCESS = 200;
    const SUCCESS_MSG = '操作成功';

    // 客户端错误 (3001-3099)
    const INVALID_PARAMS = 3001;
    const INVALID_PARAMS_MSG = '参数验证失败';

    const UNAUTHORIZED = 3002;
    const UNAUTHORIZED_MSG = '请先登录';

    const FORBIDDEN = 3003;
    const FORBIDDEN_MSG = '无权限访问';

    const NOT_FOUND = 3004;
    const NOT_FOUND_MSG = '资源不存在';

    const TOKEN_EXPIRED = 3005;
    const TOKEN_EXPIRED_MSG = '登录已过期，请重新登录';

    const TOKEN_INVALID = 3006;
    const TOKEN_INVALID_MSG = '无效的令牌';

    const TOO_MANY_REQUESTS = 3007;
    const TOO_MANY_REQUESTS_MSG = '请求过于频繁';

    // 业务逻辑错误 (3100-3199)
    const BUSINESS_ERROR = 3100;
    const BUSINESS_ERROR_MSG = '业务处理失败';

    const ADMIN_NOT_FOUND = 3101;
    const ADMIN_NOT_FOUND_MSG = '管理员不存在';

    const ADMIN_DISABLED = 3102;
    const ADMIN_DISABLED_MSG = '账号已被禁用';

    const PASSWORD_ERROR = 3103;
    const PASSWORD_ERROR_MSG = '密码错误';

    const CAPTCHA_ERROR = 3104;
    const CAPTCHA_ERROR_MSG = '验证码错误';

    const DATA_NOT_FOUND = 3105;
    const DATA_NOT_FOUND_MSG = '数据不存在';

    const DATA_ALREADY_EXISTS = 3106;
    const DATA_ALREADY_EXISTS_MSG = '数据已存在';

    const OPERATION_FAILED = 3107;
    const OPERATION_FAILED_MSG = '操作失败';

    const NO_PERMISSION = 3108;
    const NO_PERMISSION_MSG = '没有操作权限';

    // 服务端错误 (3200-3299)
    const SERVER_ERROR = 3200;
    const SERVER_ERROR_MSG = '服务器内部错误';

    /**
     * 获取状态码对应的消息
     */
    public static function getMessage(int $code): string
    {
        $messages = [
            self::SUCCESS => self::SUCCESS_MSG,
            self::INVALID_PARAMS => self::INVALID_PARAMS_MSG,
            self::UNAUTHORIZED => self::UNAUTHORIZED_MSG,
            self::FORBIDDEN => self::FORBIDDEN_MSG,
            self::NOT_FOUND => self::NOT_FOUND_MSG,
            self::TOKEN_EXPIRED => self::TOKEN_EXPIRED_MSG,
            self::TOKEN_INVALID => self::TOKEN_INVALID_MSG,
            self::TOO_MANY_REQUESTS => self::TOO_MANY_REQUESTS_MSG,
            self::BUSINESS_ERROR => self::BUSINESS_ERROR_MSG,
            self::ADMIN_NOT_FOUND => self::ADMIN_NOT_FOUND_MSG,
            self::ADMIN_DISABLED => self::ADMIN_DISABLED_MSG,
            self::PASSWORD_ERROR => self::PASSWORD_ERROR_MSG,
            self::CAPTCHA_ERROR => self::CAPTCHA_ERROR_MSG,
            self::DATA_NOT_FOUND => self::DATA_NOT_FOUND_MSG,
            self::DATA_ALREADY_EXISTS => self::DATA_ALREADY_EXISTS_MSG,
            self::OPERATION_FAILED => self::OPERATION_FAILED_MSG,
            self::NO_PERMISSION => self::NO_PERMISSION_MSG,
            self::SERVER_ERROR => self::SERVER_ERROR_MSG,
        ];

        return $messages[$code] ?? '未知错误';
    }
}
