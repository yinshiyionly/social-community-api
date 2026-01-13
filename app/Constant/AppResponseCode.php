<?php

namespace App\Constant;

/**
 * App 端 API 响应状态码
 */
class AppResponseCode
{
    // 成功
    const SUCCESS = 200;
    const SUCCESS_MSG = '操作成功';

    // 参数错误
    const INVALID_PARAMS = 400;
    const INVALID_PARAMS_MSG = '参数验证失败';

    // 认证相关 (401x)
    const UNAUTHORIZED = 4010;
    const UNAUTHORIZED_MSG = '请先登录';

    const TOKEN_INVALID = 4011;
    const TOKEN_INVALID_MSG = 'Token无效';

    const TOKEN_EXPIRED = 4012;
    const TOKEN_EXPIRED_MSG = '登录已过期，请重新登录';

    // 权限相关 (403x)
    const FORBIDDEN = 4030;
    const FORBIDDEN_MSG = '无权访问';

    const ACCOUNT_DISABLED = 4031;
    const ACCOUNT_DISABLED_MSG = '账号已被禁用';

    // 资源相关 (404x)
    const NOT_FOUND = 4040;
    const NOT_FOUND_MSG = '资源不存在';

    const DATA_NOT_FOUND = 4041;
    const DATA_NOT_FOUND_MSG = '数据不存在';

    // 请求限制 (429)
    const TOO_MANY_REQUESTS = 4290;
    const TOO_MANY_REQUESTS_MSG = '请求过于频繁，请稍后重试';

    // 服务端错误 (500x)
    const SERVER_ERROR = 5000;
    const SERVER_ERROR_MSG = '服务器繁忙，请稍后重试';

    const SERVICE_UNAVAILABLE = 5030;
    const SERVICE_UNAVAILABLE_MSG = '功能维护中，请稍后再试';

    // 业务逻辑错误 (600x)
    const BUSINESS_ERROR = 6000;
    const BUSINESS_ERROR_MSG = '操作失败';

    const NEED_VERIFICATION = 6001;
    const NEED_VERIFICATION_MSG = '请先完成实名认证';

    const NEED_BIND_PHONE = 6002;
    const NEED_BIND_PHONE_MSG = '请先绑定手机号';

    const NEED_UPDATE = 6003;
    const NEED_UPDATE_MSG = '当前版本过低，请更新后使用';

    const OPERATION_FAILED = 6004;
    const OPERATION_FAILED_MSG = '操作失败';

    /**
     * 获取状态码对应的消息
     */
    public static function getMessage(int $code): string
    {
        $messages = [
            self::SUCCESS => self::SUCCESS_MSG,
            self::INVALID_PARAMS => self::INVALID_PARAMS_MSG,
            self::UNAUTHORIZED => self::UNAUTHORIZED_MSG,
            self::TOKEN_INVALID => self::TOKEN_INVALID_MSG,
            self::TOKEN_EXPIRED => self::TOKEN_EXPIRED_MSG,
            self::FORBIDDEN => self::FORBIDDEN_MSG,
            self::ACCOUNT_DISABLED => self::ACCOUNT_DISABLED_MSG,
            self::NOT_FOUND => self::NOT_FOUND_MSG,
            self::DATA_NOT_FOUND => self::DATA_NOT_FOUND_MSG,
            self::TOO_MANY_REQUESTS => self::TOO_MANY_REQUESTS_MSG,
            self::SERVER_ERROR => self::SERVER_ERROR_MSG,
            self::SERVICE_UNAVAILABLE => self::SERVICE_UNAVAILABLE_MSG,
            self::BUSINESS_ERROR => self::BUSINESS_ERROR_MSG,
            self::NEED_VERIFICATION => self::NEED_VERIFICATION_MSG,
            self::NEED_BIND_PHONE => self::NEED_BIND_PHONE_MSG,
            self::NEED_UPDATE => self::NEED_UPDATE_MSG,
        ];

        return $messages[$code] ?? '未知错误';
    }
}
