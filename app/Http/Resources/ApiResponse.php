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
            'rows' => $items,
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
    public static function validationError($errors, $message = '验证失败')
    {
        return response()->json([
            'code' => ResponseCode::BUSINESS_ERROR,
            'msg' => $message,
            'errors' => $errors,
        ], 422);
    }

    /**
     * 未授权响应
     */
    public static function unauthorized($message = '未授权')
    {
        return response()->json([
            'code' => ResponseCode::BUSINESS_ERROR,
            'msg' => $message,
        ], 401);
    }

    /**
     * 禁止访问响应
     */
    public static function forbidden($message = '禁止访问')
    {
        return response()->json([
            'code' => ResponseCode::BUSINESS_ERROR,
            'msg' => $message,
        ], 403);
    }

    /**
     * 未找到响应
     */
    public static function notFound($message = '资源未找到')
    {
        return response()->json([
            'code' => ResponseCode::BUSINESS_ERROR,
            'msg' => $message,
        ], 404);
    }

    /**
     * 服务器错误响应
     */
    public static function serverError($message = '服务器错误')
    {
        return response()->json([
            'code' => ResponseCode::BUSINESS_ERROR,
            'msg' => $message,
        ], 500);
    }
}
