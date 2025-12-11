<?php

namespace App\Helper\Volcengine;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;

class InsightAPI
{
    protected string $baseURL = 'https://insight.volcengineapi.com/';
    protected string $xInsightBizName = '2113336772';
    protected string $xInsightBizSecret = 'GKSEKFlMv9vwkl4pcODaqNo2Jt9Z1EAd';
    protected $accessToken = null;
    protected $tokenExpireAt = null;
    protected Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => $this->baseURL,
            'timeout' => 30,
        ]);
    }

    /**
     * Generate a cache key for storing token-related data
     *
     * @param string $suffix The suffix to append to the cache key (e.g., 'access_token', 'expire_at')
     * @return string The generated cache key
     */
    protected function getCacheKey(string $suffix): string
    {
        return 'volcengine_insight_' . md5($this->xInsightBizName) . '_' . $suffix;
    }

    protected function getHeaders(bool $withAuth = false): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'X-Insight-Biz-Name' => $this->xInsightBizName,
            'X-Insight-Biz-Secret' => $this->xInsightBizSecret,
        ];

        // 要鉴权的接口 token 放在 header 中并且移除 secret
        if ($withAuth && $this->accessToken) {
            unset($headers['X-Insight-Biz-Secret']);
            $headers['X-Insight-Access-Token'] = $this->accessToken;
            // $headers['Authorization'] = 'Bearer ' . $this->accessToken;
        }

        return $headers;
    }

    public function getAccessToken(): ?array
    {
        // Check cache first (Requirement 2.1)
        $cachedToken = Cache::get($this->getCacheKey('access_token'));
        $cachedExpireAt = Cache::get($this->getCacheKey('expire_at'));

        // Return cached token if valid (with 60-second buffer) (Requirements 2.2, 2.3, 2.4)
        if ($cachedToken && $cachedExpireAt && time() < $cachedExpireAt - 60) {
            $this->accessToken = $cachedToken;
            $this->tokenExpireAt = $cachedExpireAt;
            return ['access_token' => $cachedToken, 'expires_at' => $cachedExpireAt];
        }

        // Fetch new token from API (Requirements 2.3, 2.4)
        try {
            $response = $this->client->get('oauth/access_token', [
                'headers' => $this->getHeaders()
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            if (isset($result['access_token'])) {
                $expiresIn = $result['expires_in'] ?? 7200;
                $expireAt = time() + $expiresIn;

                // Update instance properties
                $this->accessToken = $result['access_token'];
                $this->tokenExpireAt = $expireAt;

                // Store in cache with TTL of expires_in - 60 seconds (Requirements 1.1, 1.2, 3.2)
                $cacheTtl = max($expiresIn - 60, 0);
                Cache::put($this->getCacheKey('access_token'), $result['access_token'], $cacheTtl);
                Cache::put($this->getCacheKey('expire_at'), $expireAt, $cacheTtl);
            }

            return $result;
        } catch (GuzzleException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function bizSubCreateTask(array $body)
    {
        try {
            $response = $this->client->post('openapi/biz_sub/create_task', [
                'headers' => $this->getHeaders(),
                'json' => $body
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            if (isset($result['status']) && $result['status'] == 0) {
                return $result;
            }
            $msg = '[火山内容洞察]创建实时任务失败: ' . $result['message'] ?? '未知错误';
            $this->errorLog($msg, ['body' => $body, 'result' => $result]);
            throw new \Exception($msg);
        } catch (GuzzleException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function bizSubUpdateTask(array $body)
    {
        try {
            /*$body = [
                'rule' => [],
                'enable_status' => 0, // 任务开启状态 1是开启 0是关闭
                'task_id' => 1, // 更改的任务ID序号，必填
                'sync_mode' => false, // false-异步 true-同步，默认false
            ];*/
            $response = $this->client->post('openapi/biz_sub/update_task', [
                'headers' => $this->getHeaders(),
                'json' => $body
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            if (isset($result['status']) && $result['status'] == 0) {
                return $result;
            }
            $msg = '[火山内容洞察]更新实时任务失败: ' . $result['message'] ?? '未知错误';
            $this->errorLog($msg, ['body' => $body, 'result' => $result]);
            throw new \Exception($msg);
        } catch (GuzzleException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * 获取任务状态接口
     *
     * @param int $taskId
     * @return array|mixed
     * @throws \Exception
     */
    public function bizSubGetTaskRule(int $taskId)
    {
        try {
            $response = $this->client->get('openapi/biz_sub/get_task_rule', [
                'headers' => $this->getHeaders(),
                'query' => [
                    'task_id' => $taskId
                ]
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            if (isset($result['status']) && $result['status'] == 0) {
                return $result;
            }
            $msg = '[火山内容洞察]获取任务状态接口失败: ' . $result['message'] ?? '未知错误';
            $this->errorLog($msg, ['task_id' => $taskId, 'result' => $result]);
            throw new \Exception($msg);
        } catch (GuzzleException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * 检查 token 是否有效
     * Checks both instance properties and cache for token validity
     * Applies 60-second buffer before actual expiration (Requirement 3.1)
     */
    public function isTokenValid(): bool
    {
        // First check instance properties
        if ($this->accessToken !== null && $this->tokenExpireAt !== null) {
            return time() < $this->tokenExpireAt - 60; // 60-second buffer
        }

        // Fall back to checking cache for expiration timestamp (Requirement 3.1)
        $cachedToken = Cache::get($this->getCacheKey('access_token'));
        $cachedExpireAt = Cache::get($this->getCacheKey('expire_at'));

        if ($cachedToken && $cachedExpireAt) {
            // Apply 60-second buffer before actual expiration
            return time() < $cachedExpireAt - 60;
        }

        return false;
    }

    /**
     * 确保有有效的 token
     */
    protected function ensureToken(): void
    {
        if (!$this->isTokenValid()) {
            $this->getAccessToken();
        }
    }

    protected function errorLog(string $msg, array $params)
    {
        Log::channel('daily')->error($msg, $params);
    }

    /**
     * 发送 GET 请求
     */
    public function get(string $path, array $query = []): ?array
    {
        $this->ensureToken();

        try {
            $response = $this->client->get($path, [
                'headers' => $this->getHeaders(true),
                'query' => $query,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * 发送 POST 请求
     */
    public function post(string $path, array $data = []): ?array
    {
        $this->ensureToken();

        try {
            $response = $this->client->post($path, [
                'headers' => $this->getHeaders(true),
                'json' => $data,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * 设置凭证
     * Clears cached token for previous credentials before updating (Requirements 4.1, 4.2)
     */
    public function setCredentials(string $bizName, string $bizSecret): self
    {
        // Clear cached token for previous credentials before updating (Requirement 4.1)
        Cache::forget($this->getCacheKey('access_token'));
        Cache::forget($this->getCacheKey('expire_at'));

        // Update credentials (Requirement 4.2 - cache key prefix will use new business name)
        $this->xInsightBizName = $bizName;
        $this->xInsightBizSecret = $bizSecret;
        $this->accessToken = null;
        $this->tokenExpireAt = null;
        return $this;
    }
}
