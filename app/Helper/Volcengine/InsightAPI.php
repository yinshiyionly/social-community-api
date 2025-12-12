<?php

namespace App\Helper\Volcengine;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class InsightAPI
{
    protected string $baseURL = 'https://insight.volcengineapi.com/';
    protected string $xInsightBizName = '2113336772';
    protected string $xInsightBizSecret = 'GKSEKFlMv9vwkl4pcODaqNo2Jt9Z1EAd';
    protected Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => $this->baseURL,
            'timeout' => 30,
        ]);
    }

    /**
     * 生成缓存key
     *
     * @param string $suffix The suffix to append to the cache key (e.g., 'access_token', 'expire_at')
     * @return string The generated cache key
     */
    protected function getCacheKey(string $suffix): string
    {
        return 'volcengine_insight_' . md5($this->xInsightBizName) . '_' . $suffix;
    }

    /**
     * 获取请求头
     *
     * @param bool $withAuth
     * @return array
     * @throws \Exception
     */
    protected function getHeaders(bool $withAuth = false): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'X-Insight-Biz-Name' => $this->xInsightBizName,
            'X-Insight-Biz-Secret' => $this->xInsightBizSecret,
        ];

        // 要鉴权的接口 token 放在 header 中并且移除 secret
        if ($withAuth) {
            unset($headers['X-Insight-Biz-Secret']);
            try {
                $headers['X-Insight-Access-Token'] = $this->getAccessToken();
            } catch (\Exception $e) {
                $msg = '获取access-token失败: ' . $e->getMessage();
                $this->errorLog($msg, []);
                throw new \Exception($msg);
            }
        }

        return $headers;
    }

    /**
     * 获取access-token
     *
     * @return array|mixed
     * @throws \Exception
     */
    public function getAccessToken()
    {
        $cachedToken = Cache::get($this->getCacheKey('access_token'));
        $cachedExpireAt = Cache::get($this->getCacheKey('expire_at'));
        if (!empty($cachedToken) && !empty($cachedExpireAt) && time() < $cachedExpireAt - 60) {
            return $cachedToken;
        }

        try {
            $response = $this->client->get('oauth/access_token', [
                'headers' => $this->getHeaders()
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            if (isset($result['status']) && $result['status'] == 0) {
                if (isset($result['data']['access_token'])) {
                    $expiresIn = $result['data']['expire'] ?? 0;
                    $expireAt = time() + $expiresIn;

                    $cacheTtl = max($expiresIn - 60, 0);
                    Cache::put($this->getCacheKey('access_token'), $result['data']['access_token'], $cacheTtl);
                    Cache::put($this->getCacheKey('expire_at'), $expireAt, $cacheTtl);

                    return $result['data']['access_token'];
                }
            }
            $msg = '[火山内容洞察]获取access-token失败: ' . $result['message'] ?? '未知错误';
            $this->errorLog($msg, ['headers' => $this->getHeaders(), 'result' => $result]);

            throw new \Exception($msg);
        } catch (GuzzleException $e) {
            $msg = '[火山内容洞察]guzzle异常access-token: ' . $e->getMessage();
            $this->errorLog($msg, []);
            throw new \Exception($msg);
        }
    }

    /**
     * 创建实时任务
     *
     * @param array $body
     * @return array|mixed
     * @throws \Exception
     */
    public function bizSubCreateTask(array $body)
    {
        try {
            /*$body = [
                'rule' => [],
                'sync_mode' => false
            ];*/
            $response = $this->client->post('openapi/biz_sub/create_task', [
                'headers' => $this->getHeaders(true),
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
            $msg = '[火山内容洞察]guzzle异常创建实时任务: ' . $e->getMessage();
            $this->errorLog($msg, []);
            throw new \Exception($msg);
        }
    }

    /**
     * 更新实时任务
     *
     * @param array $body
     * @return array|mixed
     * @throws \Exception
     */
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
                'headers' => $this->getHeaders(true),
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
            $msg = '[火山内容洞察]guzzle异常更新实时任务: ' . $e->getMessage();
            $this->errorLog($msg, []);
            throw new \Exception($msg);
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
                'headers' => $this->getHeaders(true),
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
            $msg = '[火山内容洞察]guzzle异常获取任务状态: ' . $e->getMessage();
            $this->errorLog($msg, []);
            throw new \Exception($msg);
        }
    }

    public function bizSubSensitiveWordsCheck(array $wordList)
    {
        try {
            $response = $this->client->get('openapi/biz_sub/sensitive_words_check', [
                'headers' => $this->getHeaders(true),
                'json' => [
                    'words' => $wordList
                ]
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            if (isset($result['status']) && $result['status'] == 0) {
                return $result;
            }
            $msg = '[火山内容洞察]敏感词校验接口失败: ' . $result['message'] ?? '未知错误';
            $this->errorLog($msg, ['word_list' => $wordList, 'result' => $result]);
            throw new \Exception($msg);
        } catch (GuzzleException $e) {
            $msg = '[火山内容洞察]guzzle异常敏感词校验: ' . $e->getMessage();
            $this->errorLog($msg, []);
            throw new \Exception($msg);
        }
    }

    /**
     * 记录错误日志
     *
     * @param string $msg
     * @param array $params
     * @return void
     */
    protected function errorLog(string $msg, array $params)
    {
        Log::channel('daily')->error($msg, $params);
    }
}
