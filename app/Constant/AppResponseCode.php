<?php

namespace App\Constant;

/**
 * App 端 API 响应状态码和消息常量
 */
class AppResponseCode
{
    // 成功
    const SUCCESS = 200;
    const SUCCESS_MSG = '操作成功';

    // 客户端错误 (2001-2099)
    const INVALID_PARAMS = 2001;
    const INVALID_PARAMS_MSG = '参数验证失败';

    const UNAUTHORIZED = 2002;
    const UNAUTHORIZED_MSG = '请先登录';

    const FORBIDDEN = 2003;
    const FORBIDDEN_MSG = '无权限访问';

    const NOT_FOUND = 2004;
    const NOT_FOUND_MSG = '资源不存在';

    const TOKEN_EXPIRED = 2005;
    const TOKEN_EXPIRED_MSG = '登录已过期，请重新登录';

    const TOKEN_INVALID = 2006;
    const TOKEN_INVALID_MSG = '无效的令牌';

    const TOO_MANY_REQUESTS = 2007;
    const TOO_MANY_REQUESTS_MSG = '请求过于频繁，请稍后再试';

    // 业务逻辑错误 (2100-2199)
    const BUSINESS_ERROR = 2100;
    const BUSINESS_ERROR_MSG = '业务处理失败';

    const USER_NOT_FOUND = 2101;
    const USER_NOT_FOUND_MSG = '用户不存在';

    const USER_DISABLED = 2102;
    const USER_DISABLED_MSG = '账号已被禁用';

    const PASSWORD_ERROR = 2103;
    const PASSWORD_ERROR_MSG = '密码错误';

    const CAPTCHA_ERROR = 2104;
    const CAPTCHA_ERROR_MSG = '验证码错误';

    const DATA_NOT_FOUND = 2105;
    const DATA_NOT_FOUND_MSG = '数据不存在';

    const DATA_ALREADY_EXISTS = 2106;
    const DATA_ALREADY_EXISTS_MSG = '数据已存在';

    const OPERATION_FAILED = 2107;
    const OPERATION_FAILED_MSG = '操作失败';

    // 服务端错误 (2200-2299)
    const SERVER_ERROR = 2200;
    const SERVER_ERROR_MSG = '服务器繁忙，请稍后再试';

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
            self::USER_NOT_FOUND => self::USER_NOT_FOUND_MSG,
            self::USER_DISABLED => self::USER_DISABLED_MSG,
            self::PASSWORD_ERROR => self::PASSWORD_ERROR_MSG,
            self::CAPTCHA_ERROR => self::CAPTCHA_ERROR_MSG,
            self::DATA_NOT_FOUND => self::DATA_NOT_FOUND_MSG,
            self::DATA_ALREADY_EXISTS => self::DATA_ALREADY_EXISTS_MSG,
            self::OPERATION_FAILED => self::OPERATION_FAILED_MSG,
            self::SERVER_ERROR => self::SERVER_ERROR_MSG,
        ];

        return $messages[$code] ?? '未知错误';
    }
}
