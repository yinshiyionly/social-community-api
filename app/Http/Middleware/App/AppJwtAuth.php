<?php

namespace App\Http\Middleware\App;

use App\Http\Controllers\App\MemberAuthController;
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

        // 绑定流程临时 token 只允许访问 bindPhone，避免在未完成手机号绑定前访问其他鉴权接口。
        if (($payload['token_scene'] ?? '') === 'bind_phone' && !$this->isBindPhoneRoute($request)) {
            return AppApiResponse::forbidden('请先完成手机号绑定');
        }

        // 将 member_id 注入到 request 中
        $request->attributes->set('member_id', $payload['member_id']);
        $request->attributes->set('jwt_payload', $payload);

        return $next($request);
    }

    /**
     * 判断当前请求是否为手机号绑定接口。
     *
     * @param Request $request
     * @return bool
     */
    protected function isBindPhoneRoute(Request $request): bool
    {
        $route = $request->route();
        if (!$route) {
            return false;
        }

        $actionMethod = $route->getActionMethod();
        $actionName = $route->getActionName();

        return $actionMethod === 'bindPhone'
            && strpos($actionName, MemberAuthController::class . '@') === 0;
    }
}
