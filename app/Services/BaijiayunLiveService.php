<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 百家云直播服务
 *
 * 无状态服务类，封装百家云 HTTP API
 * 所有配置从 config/services.php 的 baijiayun 配置节读取
 */
class BaijiayunLiveService
{
    /**
     * 百家云配置
     *
     * @var array
     */
    protected $config;

    /**
     * HTTP 请求超时时间（秒）
     *
     * @var int
     */
    protected $timeout = 10;

    // 角色常量
    const ROLE_PRESENTER = 1;  // 主播
    const ROLE_VIEWER = 2;     // 观众

    // 回调事件类型
    const EVENT_LIVE_START = 'live.start';
    const EVENT_LIVE_END = 'live.end';

    public function __construct()
    {
        $this->config = config('services.baijiayun', []);
    }

    /**
     * 创建直播间
     *
     * @param string $title 房间标题
     * @param string $startTime 开始时间 (Y-m-d H:i:s)
     * @param string $endTime 结束时间 (Y-m-d H:i:s)
     * @param array $options 可选参数
     * @return array ['success' => bool, 'data' => mixed, 'error_code' => string, 'error_message' => string]
     */
    public function createRoom(string $title, string $startTime, string $endTime, array $options = []): array
    {
        $params = [
            'title' => $title,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'room_type' => $options['room_type'] ?? ($this->config['default_room_type'] ?? 2),
        ];

        // 合并额外可选参数（排除已显式处理的 room_type）
        unset($options['room_type']);
        $params = array_merge($params, $options);

        return $this->sendRequest('/openapi/room/create', $params);
    }

    /**
     * 查询直播间信息
     *
     * @param string $roomId 直播间ID
     * @return array ['success' => bool, 'data' => mixed, 'error_code' => string, 'error_message' => string]
     */
    public function getRoomInfo(string $roomId): array
    {
        $params = [
            'room_id' => $roomId,
        ];

        $result = $this->sendRequest('/openapi/room/info', $params);

        // 需求 4.2: 房间不存在时记录 warning 级别日志（区别于一般 error）
        if (!$result['success']) {
            Log::warning('百家云查询直播间失败', [
                'room_id' => $roomId,
                'error_code' => $result['error_code'],
                'error_message' => $result['error_message'],
            ]);
        }

        return $result;
    }

    /**
     * 关闭直播间
     *
     * @param string $roomId 直播间ID
     * @return array ['success' => bool, 'data' => mixed, 'error_code' => string, 'error_message' => string]
     */
    public function closeRoom(string $roomId): array
    {
        $params = [
            'room_id' => $roomId,
        ];

        return $this->sendRequest('/openapi/room/close', $params);
    }

    /**
     * 生成用户令牌
     *
     * 根据角色类型为用户生成加入直播间的令牌，令牌中包含用户标识、角色和昵称信息。
     *
     * @param string $roomId 直播间ID
     * @param string $userId 用户标识
     * @param string $nickname 用户昵称
     * @param int $role 角色类型 (ROLE_PRESENTER / ROLE_VIEWER)
     * @param string $avatar 用户头像URL（可选）
     * @return array ['success' => bool, 'data' => mixed, 'error_code' => string, 'error_message' => string]
     */
    public function generateToken(string $roomId, string $userId, string $nickname, int $role, string $avatar = ''): array
    {
        // 验证角色类型
        if (!in_array($role, [self::ROLE_PRESENTER, self::ROLE_VIEWER])) {
            Log::warning('百家云生成令牌失败：无效的角色类型', [
                'room_id' => $roomId,
                'user_id' => $userId,
                'role' => $role,
            ]);
            return $this->buildResult(false, null, 'INVALID_ROLE', '无效的角色类型');
        }

        $params = [
            'room_id' => $roomId,
            'user_number' => $userId,
            'user_name' => $nickname,
            'user_role' => $role,
        ];

        if ($avatar !== '') {
            $params['user_avatar'] = $avatar;
        }

        return $this->sendRequest('/openapi/room/enter', $params);
    }

    /**
     * 获取回放列表
     *
     * @param string $roomId 直播间ID
     * @return array ['success' => bool, 'data' => mixed, 'error_code' => string, 'error_message' => string]
     */
    public function getPlaybackList(string $roomId): array
    {
        $params = [
            'room_id' => $roomId,
        ];

        return $this->sendRequest('/openapi/playback/list', $params);
    }

    /**
     * 获取回放播放地址
     *
     * @param string $playbackId 回放ID
     * @return array ['success' => bool, 'data' => mixed, 'error_code' => string, 'error_message' => string]
     */
    public function getPlaybackUrl(string $playbackId): array
    {
        $params = [
            'video_id' => $playbackId,
        ];

        return $this->sendRequest('/openapi/playback/url', $params);
    }

    /**
     * 验证并解析回调数据
     *
     * 验证百家云回调请求的签名有效性，签名通过后解析回调数据。
     * 支持 live.start（直播开始）和 live.end（直播结束）事件。
     *
     * @param array $callbackData 回调请求数据
     * @return array ['success' => bool, 'data' => mixed, 'error_code' => string, 'error_message' => string]
     */
    public function verifyCallback(array $callbackData): array
    {
        // 检查必要字段
        if (!isset($callbackData['sign']) || !isset($callbackData['event']) || !isset($callbackData['room_id'])) {
            Log::error('百家云回调数据格式错误：缺少必要字段', [
                'has_sign' => isset($callbackData['sign']),
                'has_event' => isset($callbackData['event']),
                'has_room_id' => isset($callbackData['room_id']),
            ]);
            return $this->buildResult(false, null, 'INVALID_CALLBACK', '回调数据格式错误：缺少必要字段');
        }

        // 提取签名
        $providedSign = $callbackData['sign'];

        // 移除 sign 后重新计算签名
        $params = $callbackData;
        unset($params['sign']);

        $expectedSign = $this->generateSign($params);

        // 验证签名
        if ($providedSign !== $expectedSign) {
            Log::warning('百家云回调签名验证失败', [
                'room_id' => $callbackData['room_id'],
                'event' => $callbackData['event'],
            ]);
            return $this->buildResult(false, null, 'INVALID_SIGN', '回调签名验证失败');
        }

        // 签名验证通过，返回解析后的回调数据
        $data = $callbackData;
        unset($data['sign']);

        return $this->buildResult(true, $data);
    }



    /**
     * 计算 API 签名
     *
     * 签名规则：
     * 1. 将所有参数按键名字典序排序
     * 2. 拼接为 key1=value1&key2=value2 格式
     * 3. 末尾追加 partner_key
     * 4. 对拼接字符串进行 MD5 计算
     *
     * @param array $params 请求参数
     * @return string MD5 签名
     */
    protected function generateSign(array $params): string
    {
        ksort($params);

        $parts = [];
        foreach ($params as $key => $value) {
            $parts[] = $key . '=' . $value;
        }
        $queryString = implode('&', $parts);

        $partnerKey = $this->config['partner_key'] ?? '';
        $signString = $queryString . $partnerKey;

        return md5($signString);
    }

    /**
     * 构建统一返回结果
     *
     * @param bool $success 是否成功
     * @param mixed $data 数据
     * @param string $errorCode 错误码
     * @param string $errorMessage 错误信息
     * @return array
     */
    protected function buildResult(bool $success, $data = null, string $errorCode = '', string $errorMessage = ''): array
    {
        return [
            'success' => $success,
            'data' => $data,
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
        ];
    }

    /**
     * 发送 HTTP 请求
     *
     * 统一封装百家云 API 请求：自动附加 partner_id 和 sign，
     * 设置超时，捕获异常，记录日志。
     *
     * @param string $path API 路径
     * @param array $params 请求参数
     * @param string $method HTTP 方法 (POST)
     * @return array ['success' => bool, 'data' => mixed, 'error_code' => string, 'error_message' => string]
     */
    protected function sendRequest(string $path, array $params = [], string $method = 'POST'): array
    {
        try {
            // 自动附加 partner_id（签名前添加，确保 partner_id 参与签名计算）
            $params['partner_id'] = $this->config['partner_id'] ?? '';

            // 计算签名（partner_id 已在参数中，会参与签名）
            $params['sign'] = $this->generateSign($params);

            // 记录请求日志（排除敏感参数 sign、partner_id）
            Log::debug('百家云API请求', [
                'path' => $path,
                'method' => $method,
            ]);

            // 构建完整 URL
            $url = rtrim($this->config['base_url'] ?? '', '/') . '/' . ltrim($path, '/');

            // 发送 HTTP 请求
            $response = Http::timeout($this->timeout)->asForm()->post($url, $params);

            return $this->parseResponse($response, $path);
        } catch (\Exception $e) {
            Log::error('百家云API请求异常', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return $this->buildResult(false, null, 'NETWORK_ERROR', $e->getMessage());
        }
    }

    /**
     * 解析百家云 API 响应
     *
     * 检查 HTTP 状态、解析 JSON、校验业务状态码（code=0 为成功）。
     *
     * @param \Illuminate\Http\Client\Response $response HTTP 响应
     * @param string $path API 路径（用于日志）
     * @return array ['success' => bool, 'data' => mixed, 'error_code' => string, 'error_message' => string]
     */
    protected function parseResponse($response, string $path): array
    {
        $body = $response->body();

        // 空响应检查
        if (empty($body)) {
            Log::error('百家云API响应为空', ['path' => $path]);
            return $this->buildResult(false, null, 'EMPTY_RESPONSE', '百家云API响应为空');
        }

        // JSON 解析
        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('百家云API响应JSON解析失败', [
                'path' => $path,
                'json_error' => json_last_error_msg(),
            ]);
            return $this->buildResult(false, null, 'INVALID_JSON', '响应JSON解析失败: ' . json_last_error_msg());
        }

        // 业务状态码检查（百家云 code=0 表示成功）
        $code = isset($data['code']) ? (int) $data['code'] : -1;
        if ($code === 0) {
            return $this->buildResult(true, $data['data'] ?? null);
        }

        // 业务错误
        $errorCode = isset($data['code']) ? (string) $data['code'] : 'UNKNOWN';
        $errorMessage = $data['msg'] ?? ($data['message'] ?? '未知错误');

        Log::error('百家云API业务错误', [
            'path' => $path,
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
        ]);

        return $this->buildResult(false, null, $errorCode, $errorMessage);
    }

}
