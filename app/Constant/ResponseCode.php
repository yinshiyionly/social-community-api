<?php

namespace App\Constant;

/**
 * API 响应状态码和消息常量
 */
class ResponseCode
{
    // 成功
    const SUCCESS = 200;
    const SUCCESS_MSG = '操作成功';

    const INVALID_PARAM = 1201;
    const INVALID_PARAM_MSG = '操作失败';

    // 客户端错误 (1001-1099)
    const INVALID_PARAMS = 1201;
    const INVALID_PARAMS_MSG = '参数验证失败';

    const UNAUTHORIZED = 1201;
    const UNAUTHORIZED_MSG = '未授权访问';

    const FORBIDDEN = 1201;
    const FORBIDDEN_MSG = '无权限访问';

    const NOT_FOUND = 1201;
    const NOT_FOUND_MSG = '资源不存在';

    const METHOD_NOT_ALLOWED = 1201;
    const METHOD_NOT_ALLOWED_MSG = '请求方法不允许';

    const TOO_MANY_REQUESTS = 1201;
    const TOO_MANY_REQUESTS_MSG = '请求过于频繁';

    const TOKEN_EXPIRED = 1201;
    const TOKEN_EXPIRED_MSG = '登录已过期';

    const TOKEN_INVALID = 1201;
    const TOKEN_INVALID_MSG = '无效的令牌';

    // 服务端错误 (1100-1199)
    const SERVER_ERROR = 1201;
    const SERVER_ERROR_MSG = '服务器内部错误';

    const DATABASE_ERROR = 1201;
    const DATABASE_ERROR_MSG = '数据库错误';

    const SERVICE_UNAVAILABLE = 1201;
    const SERVICE_UNAVAILABLE_MSG = '服务暂时不可用';

    // 业务逻辑错误 (1200-1299)
    const BUSINESS_ERROR = 1201;
    const BUSINESS_ERROR_MSG = '业务处理失败';

    const DATA_NOT_FOUND = 1201;
    const DATA_NOT_FOUND_MSG = '数据不存在';

    const DATA_ALREADY_EXISTS = 1201;
    const DATA_ALREADY_EXISTS_MSG = '数据已存在';

    const OPERATION_FAILED = 1201;
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
            self::FORBIDDEN => self::FORBIDDEN_MSG,
            self::NOT_FOUND => self::NOT_FOUND_MSG,
            self::METHOD_NOT_ALLOWED => self::METHOD_NOT_ALLOWED_MSG,
            self::TOO_MANY_REQUESTS => self::TOO_MANY_REQUESTS_MSG,
            self::TOKEN_EXPIRED => self::TOKEN_EXPIRED_MSG,
            self::TOKEN_INVALID => self::TOKEN_INVALID_MSG,
            self::SERVER_ERROR => self::SERVER_ERROR_MSG,
            self::DATABASE_ERROR => self::DATABASE_ERROR_MSG,
            self::SERVICE_UNAVAILABLE => self::SERVICE_UNAVAILABLE_MSG,
            self::BUSINESS_ERROR => self::BUSINESS_ERROR_MSG,
            self::DATA_NOT_FOUND => self::DATA_NOT_FOUND_MSG,
            self::DATA_ALREADY_EXISTS => self::DATA_ALREADY_EXISTS_MSG,
            self::OPERATION_FAILED => self::OPERATION_FAILED_MSG,
        ];

        return $messages[$code] ?? '未知错误';
    }
}
