<?php

namespace App\Exceptions;

use App\Constant\AdminResponseCode;
use Exception;

/**
 * Admin 后台 API 业务异常类
 * 用于后台管理业务逻辑中主动抛出异常
 */
class AdminApiException extends Exception
{
    protected $code;
    protected $data;

    public function __construct(
        string $message = '',
        int $code = AdminResponseCode::BUSINESS_ERROR,
        array $data = []
    ) {
        parent::__construct($message ?: AdminResponseCode::getMessage($code), $code);
        $this->data = $data;
    }

    public function getData(): array
    {
        return $this->data;
    }

    /**
     * 渲染异常响应
     */
    public function render()
    {
        return response()->json([
            'code' => $this->code,
            'msg' => $this->message,
            'data' => $this->data,
        ]);
    }

    /**
     * 快捷方法：未授权
     */
    public static function unauthorized(string $message = ''): self
    {
        return new self($message, AdminResponseCode::UNAUTHORIZED);
    }

    /**
     * 快捷方法：Token 过期
     */
    public static function tokenExpired(string $message = ''): self
    {
        return new self($message, AdminResponseCode::TOKEN_EXPIRED);
    }

    /**
     * 快捷方法：无权限
     */
    public static function noPermission(string $message = ''): self
    {
        return new self($message, AdminResponseCode::NO_PERMISSION);
    }

    /**
     * 快捷方法：数据不存在
     */
    public static function dataNotFound(string $message = ''): self
    {
        return new self($message, AdminResponseCode::DATA_NOT_FOUND);
    }

    /**
     * 快捷方法：操作失败
     */
    public static function operationFailed(string $message = ''): self
    {
        return new self($message, AdminResponseCode::OPERATION_FAILED);
    }
}
