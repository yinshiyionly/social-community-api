<?php

namespace App\Http\Resources\App;

use App\Constant\AppResponseCode;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * App 端统一 API 响应类
 */
class AppApiResponse
{
    /**
     * 成功响应
     */
    public static function success($data = [], $message = 'success')
    {
        $response = [
            'code' => AppResponseCode::SUCCESS,
            'message' => $message,
        ];

        if ($data !== null && !empty($data)) {
            if ($data instanceof LengthAwarePaginator) {
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
    public static function error($message = 'error', $code = AppResponseCode::BUSINESS_ERROR, $data = [])
    {
        return response()->json([
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ]);
    }

    /**
     * 分页响应
     */
    public static function paginate($paginator, $resourceClass = null, $message = 'success')
    {
        $items = $paginator->items();

        if ($resourceClass && class_exists($resourceClass)) {
            $items = $resourceClass::collection(collect($items))->resolve();
        }

        return response()->json([
            'code' => AppResponseCode::SUCCESS,
            'message' => $message,
            'total' => $paginator->total(),
            'rows' => $items,
        ]);
    }

    /**
     * 游标分页响应
     */
    public static function cursorPaginate($paginator, $resourceClass = null, $message = 'success')
    {
        $items = $paginator->items();

        if ($resourceClass && class_exists($resourceClass)) {
            $items = $resourceClass::collection(collect($items))->resolve();
        }

        $nextCursor = $paginator->nextCursor();
        $prevCursor = $paginator->previousCursor();

        return response()->json([
            'code' => AppResponseCode::SUCCESS,
            'message' => $message,
            'data' => [
                'list' => $items,
                'next_cursor' => $nextCursor ? $nextCursor->encode() : null,
                'prev_cursor' => $prevCursor ? $prevCursor->encode() : null,
                'has_more' => $paginator->hasMorePages(),
            ],
        ]);
    }

    /**
     * 列表响应（非分页）
     */
    public static function collection($data, $resourceClass = null, $message = 'success')
    {
        if ($resourceClass && class_exists($resourceClass)) {
            $data = $resourceClass::collection(collect($data))->resolve();
        }

        return response()->json([
            'code' => AppResponseCode::SUCCESS,
            'message' => $message,
            'data' => $data,
        ]);
    }

    /**
     * 单个资源响应
     */
    public static function resource($data, $resourceClass = null, $message = 'success')
    {
        if ($resourceClass && class_exists($resourceClass)) {
            $data = (new $resourceClass($data))->resolve();
        }

        return response()->json([
            'code' => AppResponseCode::SUCCESS,
            'message' => $message,
            'data' => $data,
        ]);
    }

    /**
     * 验证错误响应
     */
    public static function validationError($errors, $message = '参数验证失败')
    {
        return response()->json([
            'code' => AppResponseCode::INVALID_PARAMS,
            'message' => $message,
            'errors' => $errors,
        ]);
    }

    /**
     * 未授权响应（未登录）
     */
    public static function unauthorized($message = '请先登录')
    {
        return response()->json([
            'code' => AppResponseCode::UNAUTHORIZED,
            'message' => $message,
        ]);
    }

    /**
     * Token 无效响应
     */
    public static function tokenInvalid($message = 'Token无效')
    {
        return response()->json([
            'code' => AppResponseCode::TOKEN_INVALID,
            'message' => $message,
        ]);
    }

    /**
     * Token 过期响应
     */
    public static function tokenExpired($message = '登录已过期，请重新登录')
    {
        return response()->json([
            'code' => AppResponseCode::TOKEN_EXPIRED,
            'message' => $message,
        ]);
    }

    /**
     * 禁止访问响应
     */
    public static function forbidden($message = '无权访问')
    {
        return response()->json([
            'code' => AppResponseCode::FORBIDDEN,
            'message' => $message,
        ]);
    }

    /**
     * 资源不存在响应
     */
    public static function notFound($message = '资源不存在')
    {
        return response()->json([
            'code' => AppResponseCode::NOT_FOUND,
            'message' => $message,
        ]);
    }

    /**
     * 数据不存在响应
     */
    public static function dataNotFound($message = '数据不存在')
    {
        return response()->json([
            'code' => AppResponseCode::DATA_NOT_FOUND,
            'message' => $message,
        ]);
    }

    /**
     * 服务器错误响应
     */
    public static function serverError($message = '服务器繁忙，请稍后重试')
    {
        return response()->json([
            'code' => AppResponseCode::SERVER_ERROR,
            'message' => $message,
        ]);
    }

    /**
     * 请求频繁响应
     */
    public static function tooManyRequests($message = '请求过于频繁，请稍后重试')
    {
        return response()->json([
            'code' => AppResponseCode::TOO_MANY_REQUESTS,
            'message' => $message,
        ]);
    }

    /**
     * 账号被踢出响应（其他设备登录）
     */
    public static function kickedOut($message = '您的账号在其他设备登录')
    {
        return response()->json([
            'code' => AppResponseCode::TOKEN_INVALID,
            'message' => $message,
        ]);
    }

    /**
     * 账号被禁用响应
     */
    public static function accountDisabled($message = '账号已被禁用')
    {
        return response()->json([
            'code' => AppResponseCode::ACCOUNT_DISABLED,
            'message' => $message,
        ]);
    }

    /**
     * 需要实名认证响应
     */
    public static function needVerification($message = '请先完成实名认证')
    {
        return response()->json([
            'code' => AppResponseCode::NEED_VERIFICATION,
            'message' => $message,
            'data' => ['need_verification' => true],
        ]);
    }

    /**
     * 需要绑定手机响应
     */
    public static function needBindPhone($message = '请先绑定手机号')
    {
        return response()->json([
            'code' => AppResponseCode::NEED_BIND_PHONE,
            'message' => $message,
            'data' => ['need_bind_phone' => true],
        ]);
    }

    /**
     * 版本过低响应（强制更新）
     */
    public static function needUpdate($message = '当前版本过低，请更新后使用', $updateUrl = '')
    {
        return response()->json([
            'code' => AppResponseCode::NEED_UPDATE,
            'message' => $message,
            'data' => [
                'need_update' => true,
                'update_url' => $updateUrl,
            ],
        ]);
    }

    /**
     * 功能维护中响应
     */
    public static function maintenance($message = '功能维护中，请稍后再试')
    {
        return response()->json([
            'code' => AppResponseCode::SERVICE_UNAVAILABLE,
            'message' => $message,
        ]);
    }
}
