<?php

namespace App\Services;

use Carbon\Carbon;
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
    protected $timeout = 60;

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
     * 查询直播上下课回调地址
     * @see https://dev.baijiayun.com/wiki/detail/79#-h162-164
     *
     * @return array
     */
    public function getClassCallbackUrl()
    {
        // 这是旧的-阿里云-触极科技主体的回调地址
        // https://api.tcscrm.hnchuji.cn/api/live/handle-class-event-callback
        $params = [
            'timestamp' => time()
        ];
        return $this->sendRequest('/openapi/live_account/getClassCallbackUrl', $params);
    }

    /**
     * 设置直播上下课回调地址
     * @see https://dev.baijiayun.com/wiki/detail/79#-h162-163
     * @return array
     */
    public function setClassCallbackUrl()
    {
        $params = [
            'url' => '',
            'timestamp' => time()
        ];
        return $this->sendRequest('/openapi/live_account/setClassCallbackUrl', $params);
    }

    /**
     * 创建直播间
     * @see https://dev.baijiayun.com/wiki/detail/79#-h6-8
     * @param string $title 房间标题
     * @param string $startTime 开始时间 (Y-m-d H:i:s)
     * @param string $endTime 结束时间 (Y-m-d H:i:s)
     * @param array $options 可选参数
     * @return array ['success' => bool, 'data' => mixed, 'error_code' => string, 'error_message' => string]
     */
    public function createRoom(string $title, string $startTime, string $endTime, array $options = []): array
    {
        $params = [
            // 直播课标题，不超过50个字符或汉字，超过部分将进行截取
            'title' => $title,
            // 开课时间, unix时间戳（秒），系统做统一化处理，格式化为开始分钟
            'start_time' => Carbon::make($startTime)->startOfMinute()->timestamp,
            // 下课时间, unix时间戳（秒），系统做统一化处理，格式化为结束分钟
            'end_time' => Carbon::make($endTime)->endOfMinute()->timestamp,
            // 当前unix时间戳（秒）
            'timestamp' => time(),
            // 1:一对一课（老的班型，老账号支持） 2:普通大班课 3:小班课普通版（老的班型，老账号支持）
            'type' => 2,
            // 代表普通大班课最大人数, 不传或传0表示不限制。
            'max_users' => 0,
            // 可选值, APP端模板样式，1是横屏，2是竖屏;
            'app_template' => 2
        ];
        // 其他参数
        // is_mock_live 是否是伪直播，0:否 1:是（注：需要给账号开通伪直播权限才可以创建伪直播，选择伪直播时，必须要选择mock_video_id或mock_room_id和mock_session_id；伪直播模式下，不能设置长期教室）
        if (isset($options['is_mock_live'])) {
            $params['is_mock_live'] = $options['is_mock_live'];
        }
        // mock_room_id 伪直播关联的回放教室号
        if (isset($options['mock_room_id'])) {
            $params['mock_room_id'] = $options['mock_room_id'];
        }
        // mock_session_id 伪直播关联的回放教室session_id（针对长期房间）
        if (isset($options['mock_session_id'])) {
            $params['mock_session_id'] = $options['mock_session_id'];
        }
        // mock_video_id 伪直播关联的点播视频ID
        if (isset($options['mock_video_id'])) {
            $params['mock_video_id'] = $options['mock_video_id'];
        }
        // enable_live_sell 直播带货模板属性 0：不启用 ，1：是纯视频模板，2：是ppt带货模板 ，请在教室未开始前更新
        if (isset($options['enable_live_sell'])) {
            $params['enable_live_sell'] = $options['enable_live_sell'];
        }


        return $this->sendRequest('/openapi/room/create', $params);
    }

    /**
     * 更新房间信息
     * @see https://dev.baijiayun.com/wiki/detail/79#-h6-9
     * @param int $roomId 房间ID
     * @param string $title 房间标题
     * @param string $startTime 开始时间 (Y-m-d H:i:s)
     * @param string $endTime 结束时间 (Y-m-d H:i:s)
     * @param array $options 可选参数
     * @return array ['success' => bool, 'data' => mixed, 'error_code' => string, 'error_message' => string]
     */
    public function updateRoom(int $roomId, string $title, string $startTime, string $endTime, array $options = []): array
    {
        $params = [
            // 房间ID，14位
            'room_id' => $roomId,
            // 直播课标题，不超过50个字符或汉字，超过部分将进行截取
            'title' => $title,
            // 开课时间, unix时间戳（秒），系统做统一化处理，格式化为开始分钟
            'start_time' => Carbon::make($startTime)->startOfMinute()->timestamp,
            // 下课时间, unix时间戳（秒），系统做统一化处理，格式化为结束分钟
            'end_time' => Carbon::make($endTime)->endOfMinute()->timestamp,
            // 当前unix时间戳（秒）
            'timestamp' => time(),
            // 1:一对一课（老的班型，老账号支持） 2:普通大班课 3:小班课普通版（老的班型，老账号支持）
            'type' => 2,
            // 代表普通大班课最大人数, 不传或传0表示不限制。
            'max_users' => 0,
            // 可选值, APP端模板样式，1是横屏，2是竖屏;
            'app_template' => 2
        ];
        // 其他参数
        // is_mock_live 是否是伪直播，0:否 1:是（注：需要给账号开通伪直播权限才可以创建伪直播，选择伪直播时，必须要选择mock_video_id或mock_room_id和mock_session_id；伪直播模式下，不能设置长期教室）
        if (isset($options['is_mock_live'])) {
            $params['is_mock_live'] = $options['is_mock_live'];
        }
        // mock_room_id 伪直播关联的回放教室号
        if (isset($options['mock_room_id'])) {
            $params['mock_room_id'] = $options['mock_room_id'];
        }
        // mock_session_id 伪直播关联的回放教室session_id（针对长期房间）
        if (isset($options['mock_session_id'])) {
            $params['mock_session_id'] = $options['mock_session_id'];
        }
        // mock_video_id 伪直播关联的点播视频ID
        if (isset($options['mock_video_id'])) {
            $params['mock_video_id'] = $options['mock_video_id'];
        }
        // enable_live_sell 直播带货模板属性 0：不启用 ，1：是纯视频模板，2：是ppt带货模板 ，请在教室未开始前更新
        if (isset($options['enable_live_sell'])) {
            $params['enable_live_sell'] = $options['enable_live_sell'];
        }


        return $this->sendRequest('/openapi/room/update', $params);
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
        /**
         * 官方例子
         */
        // 将参数按key进行排序
        ksort($params);
        $str = '';
        foreach ($params as $k => $val) {
            // 拼接成 key1=value1&key2=value2&...&keyN=valueN& 的形式
            $str .= "{$k}={$val}&";
        }
        // 结尾再拼上 partner_key=$partner_key
        $str .= "partner_key=" . $this->config['partner_key'];
        // 计算md5值
        return md5($str);

        /**
         *
         */
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
                'params' => $params
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
        $code = isset($data['code']) ? (int)$data['code'] : -1;
        if ($code === 0) {
            return $this->buildResult(true, $data['data'] ?? null);
        }

        // 业务错误
        $errorCode = isset($data['code']) ? (string)$data['code'] : 'UNKNOWN';
        $errorMessage = $data['msg'] ?? ($data['message'] ?? '未知错误');

        Log::error('百家云API业务错误', [
            'path' => $path,
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
        ]);

        return $this->buildResult(false, null, $errorCode, $errorMessage);
    }

}
