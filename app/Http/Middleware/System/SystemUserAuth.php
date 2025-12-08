<?php

namespace App\Http\Middleware\System;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Resources\ApiResponse;

class SystemUserAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        // 手动执行 Sanctum 的认证逻辑
        $token = $request->bearerToken();

        if (!$token) {
            return ApiResponse::error('请登录后操作', 401, 401);
        }

        $accessToken = PersonalAccessToken::findToken($token);

        if (! $accessToken || ! $accessToken->tokenable) {
            return ApiResponse::error('Token无效', 401, 401);
        }

        // todo 实现 token 有效期判断
        // token expires

        // 手动设置当前用户和访问令牌
        $user = $accessToken->tokenable;
        $user->withAccessToken($accessToken);

        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        return $next($request);
    }
}
