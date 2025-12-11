<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Insight 舆情数据同步认证中间件
 * 
 * 用于验证 Python Consumer 发送的 HTTP 请求中的 Token
 * 独立于 system.auth 中间件，专门用于数据同步接口
 */
class InsightSyncAuth
{
    /**
     * 处理传入的请求
     *
     * @param Request $request 请求对象
     * @param Closure $next 下一个中间件
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // 从请求 Header 中获取 X-Sync-Token
        $token = $request->header('X-Sync-Token');

        // 检查 Token 是否存在
        if (empty($token)) {
            return $this->unauthorizedResponse('Missing sync token');
        }

        // 获取配置中的 Token
        $configToken = config('services.insight_sync.token');

        // 检查配置是否存在
        if (empty($configToken)) {
            return $this->unauthorizedResponse('Sync token not configured');
        }

        // 验证 Token 是否匹配
        if ($token !== $configToken) {
            return $this->unauthorizedResponse('Invalid sync token');
        }

        // Token 验证通过，继续处理请求
        return $next($request);
    }

    /**
     * 返回 401 未授权响应
     *
     * @param string $message 错误信息
     * @return JsonResponse
     */
    private function unauthorizedResponse(string $message): JsonResponse
    {
        return response()->json([
            'code' => 401,
            'message' => $message,
        ], 401);
    }
}
