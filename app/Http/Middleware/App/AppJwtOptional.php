<?php

namespace App\Http\Middleware\App;

use App\Models\App\AppMemberBase;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Helper\JwtHelper;

/**
 * App 端可选 JWT 认证中间件。
 *
 * 设计约束：
 * 1. 无 token 直接放行，接口按游客语义执行；
 * 2. 有 token 时仅在 token 合法且会员状态正常时注入登录态；
 * 3. bind_phone 临时 token 不注入登录态，避免越权获取用户能力。
 */
class AppJwtOptional
{
    /**
     * 处理顺序：先验 token，再验会员状态，满足条件才注入 member_id。
     *
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
            $isBindPhoneToken = $payload && ($payload['token_scene'] ?? '') === 'bind_phone';

            // Token 有效且未过期时，注入用户信息
            if (
                $payload
                && !$isBindPhoneToken
                && !JwtHelper::isExpired($payload)
                && isset($payload['member_id'])
                && $this->isActiveMember((int)$payload['member_id'])
            ) {
                $request->attributes->set('member_id', $payload['member_id']);
                $request->attributes->set('jwt_payload', $payload);
            }
        }

        return $next($request);
    }

    /**
     * 校验会员是否有效（存在、未软删、状态正常）。
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
