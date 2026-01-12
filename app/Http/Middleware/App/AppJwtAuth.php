<?php

namespace App\Http\Middleware\App;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Helper\JwtHelper;
use App\Http\Resources\ApiResponse;

/**
 * App 端 JWT 认证中间件
 * JWT payload 中包含 member_id
 */
class AppJwtAuth
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

        $secret = config('app.jwt_app_secret', config('app.key'));
        $payload = JwtHelper::decode($token, $secret);

        if (!$payload) {
            return ApiResponse::tokenInvalid('Token无效');
        }

        if (JwtHelper::isExpired($payload)) {
            return ApiResponse::tokenExpired('Token已过期');
        }

        if (!isset($payload['member_id'])) {
            return ApiResponse::tokenInvalid('Token无效');
        }

        // 将 member_id 注入到 request 中
        $request->attributes->set('member_id', $payload['member_id']);
        $request->attributes->set('jwt_payload', $payload);

        return $next($request);
    }
}
