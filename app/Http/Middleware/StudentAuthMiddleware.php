<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Http\Resources\ApiResponse;
use App\Constant\ResponseCode;
use App\Models\Student\StudentMaster;
use Laravel\Sanctum\PersonalAccessToken;

class StudentAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // 从请求头中获取token
        $token = $request->bearerToken();

        if (!$token) {
            return ApiResponse::error('未提供访问令牌', 401, 401);
        }

        // 解析token获取学生信息
        $accessToken = PersonalAccessToken::findToken($token);

        if (!$accessToken) {
            return ApiResponse::error('无效的访问令牌', 401, 401);
        }

        // 获取关联的学生用户
        $student = $accessToken->tokenable;

        // 检查用户是否存在
        if (!$student) {
            return ApiResponse::error('令牌无效: 令牌关联的用户不存在', 401, 401);
        }

        // 检查用户类型是否为StudentMaster
        if (!$student instanceof StudentMaster) {
            return ApiResponse::error('令牌无效: 用户类型不正确', 401, 401);
        }

        // 检查学生是否被禁用
        if ($student->isDisabled()) {
            return ApiResponse::error('用户已被禁用', 401, 401);
        }

        // 将学生信息添加到请求中，以便后续使用
        $request->attributes->set('student', $student);

        return $next($request);
    }
}