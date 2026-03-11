<?php

namespace App\Http\Middleware\App;

use App\Http\Controllers\App\MemberAuthController;
use App\Http\Resources\App\AppApiResponse;
use App\Models\App\AppMemberBase;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Helper\JwtHelper;

/**
 * App 端 JWT 强制鉴权中间件。
 *
 * 拦截条件：
 * 1. 未携带 token；
 * 2. token 无效/过期；
 * 3. token 对应会员不存在、已软删或状态非正常；
 * 4. bind_phone 临时 token 越权访问非绑定接口。
 *
 * 放行条件：
 * - token 合法且会员状态正常时注入 member_id/jwt_payload 并放行。
 */
class AppJwtAuth
{
    /**
     * 认证顺序：先验 token，再验会员状态，最后校验临时 token 场景权限。
     *
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

        if (!$this->isActiveMember((int)$payload['member_id'])) {
            // 注销/禁用后立即使旧 token 失效，避免仅依赖客户端清理登录态。
            return AppApiResponse::unauthorized('登录状态已失效');
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

    /**
     * 判断会员是否处于可登录状态（存在、未软删、状态正常）。
     *
     * @param int $memberId
     * @return bool
     */
    protected function isActiveMember(int $memberId): bool
    {
        if ($memberId <= 0) {
            return false;
        }

        $member = AppMemberBase::query()
            ->select(['member_id', 'status'])
            ->where('member_id', $memberId)
            ->first();

        return $member ? $member->isNormal() : false;
    }
}
