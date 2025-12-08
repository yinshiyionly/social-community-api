<?php

namespace App\Http\Middleware;

use App\Models\System\SystemOperLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * 操作日志中间件
 * 记录所有通过此中间件的请求，用于审计和追踪
 */
class OperationLog
{
    /**
     * 不需要记录日志的路由前缀
     */
    protected $excludedPrefixes = [
        'sanctum/',
        '_debugbar/',
        'telescope/',
    ];

    /**
     * 不需要记录日志的路由名称
     */
    protected $excludedRoutes = [
        'health',
        'ping',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $title  模块标题（可选）
     * @param  int|null  $eventType  事件类型（可选）
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $title = null, $eventType = null)
    {
        try {
            // 检查是否需要排除此请求
            if ($this->shouldExclude($request)) {
                return $next($request);
            }

            // 先执行业务逻辑，确保响应正常
            $response = $next($request);

            // 记录日志（任何错误都不会影响响应）
            $this->recordOperLog($request, $response, $title, $eventType);

            return $response;
        } catch (\Throwable $e) {
            // 如果是业务逻辑的异常，直接抛出
            // 如果是日志记录的异常，已经在 recordOperLog 中捕获
            // 这里只是额外的保护层
            if ($e instanceof \Exception && !isset($response)) {
                // 业务逻辑异常，继续抛出
                throw $e;
            }
            
            // 日志记录异常，静默处理
            try {
                Log::error('操作日志中间件异常', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
            } catch (\Throwable $logError) {
                // 连日志都记录不了，完全静默
            }
            
            // 返回响应（如果有）
            return $response ?? $next($request);
        }
    }

    /**
     * 判断是否应该排除此请求
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function shouldExclude(Request $request)
    {
        try {
            $path = $request->path();

            // 检查排除的路由前缀
            foreach ($this->excludedPrefixes as $prefix) {
                if (Str::startsWith($path, $prefix)) {
                    return true;
                }
            }

            // 检查排除的路由名称
            $routeName = $request->route() ? $request->route()->getName() : null;
            if ($routeName && in_array($routeName, $this->excludedRoutes)) {
                return true;
            }

            return false;
        } catch (\Throwable $e) {
            // 判断失败时，默认不排除（记录日志）
            return false;
        }
    }

    /**
     * 记录操作日志
     * 
     * 注意：此方法的任何异常都不应该影响业务流程
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $response
     * @param  string|null  $title
     * @param  int|null  $eventType
     * @return void
     */
    protected function recordOperLog($request, $response, $title = null, $eventType = null)
    {
        try {
            // 获取当前认证用户（安全获取）
            $user = null;
            try {
                $user = $request->user();
            } catch (\Throwable $e) {
                // 用户获取失败，继续执行
            }

            // 获取路由信息（安全获取）
            $routeUri = '';
            try {
                $route = $request->route();
                $routeUri = $route ? $route->uri() : $request->path();
            } catch (\Throwable $e) {
                $routeUri = 'unknown';
            }

            // 解析事件类型（如果未指定，则根据HTTP方法推断）
            $eventType = $this->parseEventType($request, $eventType);

            // 获取模块标题（如果未指定，则使用默认值）
            $title = $title ?: '系统操作';

            // 获取请求参数（过滤敏感信息）
            $params = $this->getRequestParams($request);
            $operParam = $this->formatParams($params);

            // 获取用户代理
            $userAgent = $this->getUserAgent($request);

            // 获取IP地址
            $ip = $this->getClientIp($request);

            // 获取地理位置
            $location = $this->getLocationByIp($ip);

            // 获取操作人员信息
            $operatorId = null;
            $operName = 'guest';
            try {
                if ($user) {
                    $operatorId = $user->user_id ?? null;
                    $operName = $user->user_name ?? $user->nick_name ?? 'unknown';
                }
            } catch (\Throwable $e) {
                // 用户信息获取失败，使用默认值
            }

            // 创建日志记录
            SystemOperLog::create([
                'title' => $title,
                'event_type' => $eventType,
                'route' => $routeUri,
                'request_method' => $request->method(),
                'user_agent' => $userAgent,
                'operator_id' => $operatorId,
                'oper_name' => $operName,
                'oper_url' => $this->sanitizeUrl($request->fullUrl()),
                'oper_ip' => $ip,
                'oper_location' => $location,
                'oper_param' => $operParam,
                'event_time' => now(),
            ]);
        } catch (\Throwable $e) {
            // 记录日志失败不影响正常业务，但需要记录错误
            // 使用 Throwable 捕获所有错误和异常
            try {
                Log::error('操作日志记录失败', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'url' => $request->fullUrl() ?? 'unknown',
                    'method' => $request->method() ?? 'unknown',
                ]);
            } catch (\Throwable $logError) {
                // 连错误日志都记录不了，完全静默处理
                // 不做任何操作，确保不影响业务
            }
        }
    }

    /**
     * 获取请求参数并过滤敏感信息
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function getRequestParams(Request $request)
    {
        try {
            $params = [];

            // 获取所有输入数据
            $allInput = $request->all();

            // 过滤敏感字段
            $params = $this->filterSensitiveData($allInput);

            // 如果是文件上传，记录文件信息
            try {
                if ($request->hasFile('file') || $request->hasFile('files')) {
                    $params['_files'] = $this->getFileInfo($request);
                }
            } catch (\Throwable $e) {
                // 文件信息获取失败，忽略
            }

            return $params;
        } catch (\Throwable $e) {
            // 参数获取失败，返回空数组
            return [];
        }
    }

    /**
     * 过滤敏感数据
     *
     * @param  array  $data
     * @return array
     */
    protected function filterSensitiveData(array $data)
    {
        try {
            $sensitiveKeys = [
                'password',
                'oldPassword',
                'old_password',
                'newPassword',
                'new_password',
                'confirmPassword',
                'confirm_password',
                'password_confirmation',
                'token',
                'access_token',
                'refresh_token',
                'api_key',
                'secret',
                'api_secret',
                'private_key',
                'credit_card',
                'cvv',
                'ssn',
            ];

            foreach ($data as $key => $value) {
                try {
                    // 检查是否是敏感字段
                    $lowerKey = strtolower($key);
                    foreach ($sensitiveKeys as $sensitiveKey) {
                        if (Str::contains($lowerKey, strtolower($sensitiveKey))) {
                            $data[$key] = '******';
                            continue 2;
                        }
                    }

                    // 递归处理数组
                    if (is_array($value)) {
                        $data[$key] = $this->filterSensitiveData($value);
                    }
                } catch (\Throwable $e) {
                    // 单个字段处理失败，跳过
                    continue;
                }
            }

            return $data;
        } catch (\Throwable $e) {
            // 过滤失败，返回空数组（安全起见）
            return [];
        }
    }

    /**
     * 格式化参数为字符串
     *
     * @param  array  $params
     * @return string
     */
    protected function formatParams(array $params)
    {
        try {
            if (empty($params)) {
                return '';
            }

            $json = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            
            // json_encode 可能返回 false
            if ($json === false) {
                return '';
            }

            // 限制长度，避免数据库字段溢出
            if (strlen($json) > 2000) {
                $json = mb_substr($json, 0, 1997) . '...';
            }

            return $json;
        } catch (\Throwable $e) {
            // 格式化失败，返回空字符串
            return '';
        }
    }

    /**
     * 获取文件信息
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function getFileInfo(Request $request)
    {
        try {
            $fileInfo = [];

            foreach ($request->allFiles() as $key => $file) {
                try {
                    if (is_array($file)) {
                        foreach ($file as $index => $f) {
                            try {
                                $fileInfo["{$key}[{$index}]"] = [
                                    'name' => $f->getClientOriginalName(),
                                    'size' => $f->getSize(),
                                    'mime' => $f->getMimeType(),
                                ];
                            } catch (\Throwable $e) {
                                // 单个文件信息获取失败，跳过
                                continue;
                            }
                        }
                    } else {
                        $fileInfo[$key] = [
                            'name' => $file->getClientOriginalName(),
                            'size' => $file->getSize(),
                            'mime' => $file->getMimeType(),
                        ];
                    }
                } catch (\Throwable $e) {
                    // 单个文件处理失败，跳过
                    continue;
                }
            }

            return $fileInfo;
        } catch (\Throwable $e) {
            // 文件信息获取失败，返回空数组
            return [];
        }
    }

    /**
     * 解析事件类型
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int|null  $eventType
     * @return int
     */
    protected function parseEventType(Request $request, $eventType = null)
    {
        try {
            // 如果已指定事件类型，直接返回
            if ($eventType !== null && is_numeric($eventType)) {
                return (int) $eventType;
            }

            // 根据请求方法推断事件类型
            $method = strtoupper($request->method());

            switch ($method) {
                case 'POST':
                    // 检查是否是导入或导出操作
                    if ($this->isImportRequest($request)) {
                        return SystemOperLog::EVENT_IMPORT;
                    }
                    if ($this->isExportRequest($request)) {
                        return SystemOperLog::EVENT_EXPORT;
                    }
                    return SystemOperLog::EVENT_INSERT;

                case 'PUT':
                case 'PATCH':
                    return SystemOperLog::EVENT_UPDATE;

                case 'DELETE':
                    return SystemOperLog::EVENT_DELETE;

                case 'GET':
                    // GET请求通常是查询操作
                    return SystemOperLog::EVENT_QUERY;

                default:
                    return SystemOperLog::EVENT_OTHER;
            }
        } catch (\Throwable $e) {
            // 解析失败，返回默认值
            return SystemOperLog::EVENT_OTHER;
        }
    }

    /**
     * 判断是否是导入请求
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function isImportRequest(Request $request)
    {
        try {
            $path = $request->path();
            $routeName = $request->route() ? $request->route()->getName() : '';

            return Str::contains($path, 'import') || Str::contains($routeName, 'import');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 判断是否是导出请求
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function isExportRequest(Request $request)
    {
        try {
            $path = $request->path();
            $routeName = $request->route() ? $request->route()->getName() : '';

            return Str::contains($path, 'export') || Str::contains($routeName, 'export');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 获取用户代理
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function getUserAgent(Request $request)
    {
        try {
            $userAgent = $request->userAgent() ?? '';

            // 限制长度
            if (strlen($userAgent) > 200) {
                $userAgent = substr($userAgent, 0, 197) . '...';
            }

            return $userAgent;
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * 获取客户端IP地址
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function getClientIp(Request $request)
    {
        try {
            // 尝试从多个头部获取真实IP
            $headers = [
                'HTTP_X_FORWARDED_FOR',
                'HTTP_X_REAL_IP',
                'HTTP_CLIENT_IP',
                'HTTP_X_FORWARDED',
                'HTTP_FORWARDED_FOR',
                'HTTP_FORWARDED',
            ];

            foreach ($headers as $header) {
                try {
                    if ($request->server($header)) {
                        $ips = explode(',', $request->server($header));
                        $ip = trim($ips[0]);
                        
                        if (filter_var($ip, FILTER_VALIDATE_IP)) {
                            return $ip;
                        }
                    }
                } catch (\Throwable $e) {
                    // 单个头部获取失败，继续尝试下一个
                    continue;
                }
            }

            return $request->ip() ?? '0.0.0.0';
        } catch (\Throwable $e) {
            return '0.0.0.0';
        }
    }

    /**
     * 根据IP获取地理位置
     *
     * @param  string  $ip
     * @return string
     */
    protected function getLocationByIp($ip)
    {
        try {
            // 内网IP
            if ($ip === '127.0.0.1' || $ip === '::1' || $ip === '0.0.0.0') {
                return '内网IP';
            }

            // 检查是否是私有IP
            if ($this->isPrivateIp($ip)) {
                return '内网IP';
            }

            // TODO: 可以集成第三方IP地理位置服务
            // 例如：ip2region, GeoIP2, 或者调用API服务
            
            return '未知位置';
        } catch (\Throwable $e) {
            return '未知位置';
        }
    }

    /**
     * 判断是否是私有IP
     *
     * @param  string  $ip
     * @return bool
     */
    protected function isPrivateIp($ip)
    {
        try {
            return !filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            );
        } catch (\Throwable $e) {
            return true; // 出错时默认认为是私有IP
        }
    }

    /**
     * 清理URL，移除敏感参数
     *
     * @param  string  $url
     * @return string
     */
    protected function sanitizeUrl($url)
    {
        try {
            // 移除URL中的token等敏感参数
            $parsed = parse_url($url);
            
            if ($parsed === false) {
                return $url;
            }
            
            if (isset($parsed['query'])) {
                parse_str($parsed['query'], $params);
                
                $sensitiveParams = ['token', 'access_token', 'api_key', 'secret'];
                foreach ($sensitiveParams as $param) {
                    if (isset($params[$param])) {
                        $params[$param] = '******';
                    }
                }
                
                $parsed['query'] = http_build_query($params);
            }

            // 重建URL
            $scheme = isset($parsed['scheme']) ? $parsed['scheme'] . '://' : '';
            $host = $parsed['host'] ?? '';
            $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
            $path = $parsed['path'] ?? '';
            $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';

            $sanitizedUrl = $scheme . $host . $port . $path . $query;

            // 限制长度
            if (strlen($sanitizedUrl) > 255) {
                $sanitizedUrl = substr($sanitizedUrl, 0, 252) . '...';
            }

            return $sanitizedUrl;
        } catch (\Throwable $e) {
            // URL处理失败，返回原URL或截断版本
            try {
                if (strlen($url) > 255) {
                    return substr($url, 0, 252) . '...';
                }
                return $url;
            } catch (\Throwable $e2) {
                return 'unknown';
            }
        }
    }
}
