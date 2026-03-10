<?php

namespace App\Http\Middleware\App;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Helper\JwtHelper;

/**
 * App 端可选 JWT 认证中间件
 * 有 token 时解析用户信息，无 token 时也允许访问
 */
class AppJwtOptional
{
    /**
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if ($token) {
            $secret = config('app.jwt_app_secret', config('app.key'));
            $payload = JwtHelper::decode($token, $secret);

            // 绑定流程临时 token 不参与可选鉴权，避免在未绑定前拿到登录态能力。
            $isBindPhoneToken = ($payload['token_scene'] ?? '') === 'bind_phone';

            // Token 有效且未过期时，注入用户信息
            if (
                $payload
                && !$isBindPhoneToken
                && !JwtHelper::isExpired($payload)
                && isset($payload['member_id'])
            ) {
                $request->attributes->set('member_id', $payload['member_id']);
                $request->attributes->set('jwt_payload', $payload);
            }
        }

        return $next($request);
    }
}
