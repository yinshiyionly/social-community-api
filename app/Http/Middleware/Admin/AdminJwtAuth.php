<?php

namespace App\Http\Middleware\Admin;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Helper\JwtHelper;
use App\Http\Resources\ApiResponse;

/**
 * Admin 端 JWT 认证中间件
 * JWT payload 中包含 user_id
 */
class AdminJwtAuth
{
    /**
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return ApiResponse::unauthorized('请登录后操作');
        }

        $secret = config('app.jwt_admin_secret', config('app.key'));
        $payload = JwtHelper::decode($token, $secret);

        if (!$payload) {
            return ApiResponse::tokenInvalid('Token无效');
        }

        if (JwtHelper::isExpired($payload)) {
            return ApiResponse::tokenExpired('Token已过期');
        }

        if (!isset($payload['user_id'])) {
            return ApiResponse::tokenInvalid('Token无效');
        }

        // 将 user_id 注入到 request 中
        $request->attributes->set('user_id', $payload['user_id']);
        $request->attributes->set('jwt_payload', $payload);

        return $next($request);
    }
}
