<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$permissions)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'code' => 401,
                'msg' => '未登录或登录已过期'
            ], 401);
        }

        // 管理员拥有所有权限
        if ($user->isAdmin()) {
            return $next($request);
        }
        // todo 后台权限暂时放开
        return $next($request);


        // 检查权限
        $userPermissions = $user->getPermissions();

        foreach ($permissions as $permission) {
            if (in_array($permission, $userPermissions)) {
                return $next($request);
            }
        }

        return response()->json([
            'code' => 403,
            'msg' => '没有访问权限'
        ], 403);
    }
}
