<?php

namespace App\Exceptions;

use App\Constant\ResponseCode;
use Exception;

/**
 * API 业务异常类
 * 用于在业务逻辑中主动抛出异常
 */
class ApiException extends Exception
{
    protected $code;
    protected $data;

    public function __construct(
        string $message = '',
        int $code = ResponseCode::BUSINESS_ERROR,
        array $data = []
    ) {
        parent::__construct($message ?: ResponseCode::getMessage($code), $code);
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
}
