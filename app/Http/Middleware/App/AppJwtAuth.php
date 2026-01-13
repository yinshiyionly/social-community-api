<?php

namespace App\Http\Middleware\App;

use App\Http\Resources\App\AppApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Helper\JwtHelper;

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
            return AppApiResponse::unauthorized();
        }

        $secret = config('app.jwt_app_secret', config('app.key'));
        $payload = JwtHelper::decode($token, $secret);

        if (!$payload) {
            return AppApiResponse::tokenInvalid();
        }

        if (JwtHelper::isExpired($payload)) {
            return AppApiResponse::tokenExpired();
        }

        if (!isset($payload['member_id'])) {
            return AppApiResponse::tokenInvalid();
        }

        // 将 member_id 注入到 request 中
        $request->attributes->set('member_id', $payload['member_id']);
        $request->attributes->set('jwt_payload', $payload);

        return $next($request);
    }
}
