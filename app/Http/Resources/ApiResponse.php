<?php

namespace App\Http\Resources;

use App\Constant\ResponseCode;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\JsonResource;

class ApiResponse
{
    /**
     * 成功响应
     */
    public static function success($data = [], $message = '操作成功')
    {
        $response = [
            'code' => ResponseCode::SUCCESS,
            'msg' => $message,
        ];

        if ($data !== null && !empty($data)) {
            if ($data instanceof LengthAwarePaginator) {
                // 分页数据
                $response['total'] = $data->total();
                $response['rows'] = $data->items();
            } else {
                return response()->json(array_merge($response, $data));
            }
        } else {
            $response['data'] = [];
        }

        return response()->json($response);
    }

    /**
     * 错误响应
     */
    public static function error($message = '操作失败', $code = ResponseCode::BUSINESS_ERROR, $status = 200, $data = [])
    {
        return response()->json([
            'code' => $code,
            'msg' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * 分页响应
     */
    public static function paginate($paginator, $resourceClass = null, $message = '查询成功')
    {
        $items = $paginator->items();

        if ($resourceClass && class_exists($resourceClass)) {
            $items = $resourceClass::collection(collect($items))->resolve();
        }

        return response()->json([
            'code' => ResponseCode::SUCCESS,
            'msg' => $message,
            'total' => $paginator->total(),
            'rows' => $items
        ]);
    }

    /**
     * 列表响应（非分页）
     */
    public static function collection($data, $resourceClass = null, $message = '查询成功')
    {
        if ($resourceClass && class_exists($resourceClass)) {
            $data = $resourceClass::collection(collect($data))->resolve();
        }

        return response()->json([
            'code' => ResponseCode::SUCCESS,
            'msg' => $message,
            'data' => $data,
        ]);
    }

    /**
     * 单个资源响应
     */
    public static function resource($data, $resourceClass = null, $message = '查询成功')
    {
        if ($resourceClass && class_exists($resourceClass)) {
            $data = (new $resourceClass($data))->resolve();
        }

        return response()->json([
            'code' => ResponseCode::SUCCESS,
            'msg' => $message,
            'data' => $data,
        ]);
    }

    /**
     * 创建成功响应
     */
    public static function created($data = [], $message = '创建成功')
    {
        return self::success($data, $message);
    }

    /**
     * 更新成功响应
     */
    public static function updated($data = [], $message = '更新成功')
    {
        return self::success($data, $message);
    }

    /**
     * 删除成功响应
     */
    public static function deleted($message = '删除成功')
    {
        return self::success([], $message);
    }

    /**
     * 无内容响应
     */
    public static function noContent()
    {
        return response()->json(null, 204);
    }

    /**
     * 验证错误响应
     */
    public static function validationError($errors, $message = '参数验证失败')
    {
        return response()->json([
            'code' => ResponseCode::INVALID_PARAM_MSG,
            'msg' => $message,
            'errors' => $errors,
        ], 200);
    }

    /**
     * 未授权响应
     */
    public static function unauthorized($message = '未登录或登录已过期')
    {
        return response()->json([
            'code' => ResponseCode::UNAUTHORIZED,
            'msg' => $message,
        ], 200);
    }

    /**
     * 禁止访问响应
     */
    public static function forbidden($message = '禁止访问')
    {
        return response()->json([
            'code' => ResponseCode::FORBIDDEN,
            'msg' => $message,
        ], 200);
    }

    /**
     * 未找到响应
     */
    public static function notFound($message = '资源不存在')
    {
        return response()->json([
            'code' => ResponseCode::NOT_FOUND,
            'msg' => $message,
        ], 200);
    }

    /**
     * 服务器错误响应
     */
    public static function serverError($message = '服务器内部错误')
    {
        return response()->json([
            'code' => ResponseCode::SERVER_ERROR,
            'msg' => $message,
        ], 200);
    }

    /**
     * Token 无效响应
     */
    public static function tokenInvalid($message = 'Token无效')
    {
        return response()->json([
            'code' => ResponseCode::TOKEN_INVALID,
            'msg' => $message,
        ], 200);
    }

    /**
     * Token 过期响应
     */
    public static function tokenExpired($message = 'Token已过期')
    {
        return response()->json([
            'code' => ResponseCode::TOKEN_EXPIRED,
            'msg' => $message,
        ], 200);
    }

    /**
     * 无权限响应
     */
    public static function noPermission($message = '无操作权限')
    {
        return response()->json([
            'code' => ResponseCode::INVALID_PARAM_MSG,
            'msg' => $message,
        ], 200);
    }

    /**
     * 数据已存在响应
     */
    public static function dataExists($message = '数据已存在')
    {
        return response()->json([
            'code' => ResponseCode::INVALID_PARAM_MSG,
            'msg' => $message,
        ], 200);
    }

    /**
     * 数据不存在响应
     */
    public static function dataNotExists($message = '数据不存在')
    {
        return response()->json([
            'code' => ResponseCode::INVALID_PARAM_MSG,
            'msg' => $message,
        ], 200);
    }

    /**
     * 强制登出响应（前端直接跳转登录页）
     * 对应前端 VITE_SERVICE_LOGOUT_CODES
     */
    public static function forceLogout($message = '请重新登录')
    {
        return response()->json([
            'code' => ResponseCode::INVALID_PARAM_MSG,
            'msg' => $message,
        ], 200);
    }

    /**
     * 会话过期响应（前端直接跳转登录页）
     * 对应前端 VITE_SERVICE_LOGOUT_CODES
     */
    public static function sessionExpired($message = '会话已失效，请重新登录')
    {
        return response()->json([
            'code' => ResponseCode::INVALID_PARAM_MSG,
            'msg' => $message,
        ], 200);
    }

    /**
     * 模态框登出响应（前端弹出模态框提示）
     * 对应前端 VITE_SERVICE_MODAL_LOGOUT_CODES
     */
    public static function modalLogout($message = '您已被强制登出')
    {
        return response()->json([
            'code' => ResponseCode::INVALID_PARAM_MSG,
            'msg' => $message,
        ], 200);
    }

    /**
     * 账号被踢出响应（前端弹出模态框提示）
     * 对应前端 VITE_SERVICE_MODAL_LOGOUT_CODES
     */
    public static function kickedOut($message = '您的账号在其他设备登录')
    {
        return response()->json([
            'code' => ResponseCode::INVALID_PARAM_MSG,
            'msg' => $message,
        ], 200);
    }

    /**
     * Token 需要刷新响应（前端自动刷新 Token 并重发请求）
     * 对应前端 VITE_SERVICE_EXPIRED_TOKEN_CODES
     */
    public static function tokenRefreshRequired($message = 'Token需要刷新')
    {
        return response()->json([
            'code' => ResponseCode::INVALID_PARAM_MSG,
            'msg' => $message,
        ], 200);
    }
}
