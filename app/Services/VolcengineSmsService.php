<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Volc\Service\Sms;

/**
 * 火山云短信服务
 *
 * 无状态服务类，可在 Job、Controller 等场景中直接使用
 * 所有配置从 config/services.php 读取
 */
class VolcengineSmsService
{
    /**
     * 短信配置
     *
     * @var array
     */
    protected $config;

    /**
     * 火山云 SMS 客户端
     *
     * @var Sms
     */
    protected $client;

    /**
     * 模板变量名映射
     */
    const TEMPLATE_VAR_CODE = 'xxxx';

    public function __construct()
    {
        $this->config = config('services.volcengine.sms', []);
        $this->initClient();
    }

    /**
     * 初始化火山云 SMS 客户端
     *
     * @return void
     */
    protected function initClient(): void
    {
        $region = $this->config['region'] ?? 'cn-north-1';
        $this->client = Sms::getInstance($region);
        $this->client->setAccessKey($this->config['access_key'] ?? '');
        $this->client->setSecretKey($this->config['secret_key'] ?? '');
    }

    /**
     * 发送验证码短信
     *
     * @param string $phoneNumber 手机号
     * @param string $code 验证码
     * @param string $templateId 模板ID
     * @return array ['success' => bool, 'request_id' => string, 'error_code' => string, 'error_message' => string]
     */
    public function sendVerificationCode(string $phoneNumber, string $code, string $templateId): array
    {
        $templateParams = [
            self::TEMPLATE_VAR_CODE => $code
        ];
        $tag = sprintf("%s-%d", $phoneNumber, time());

        return $this->send($phoneNumber, $templateId, $templateParams, $tag);
    }

    /**
     * 发送短信（通用方法）
     *
     * @param string $phoneNumber 手机号
     * @param string $templateId 模板ID
     * @param array $templateParams 模板参数
     * @param string|null $tag 标签（可选）
     * @return array ['success' => bool, 'request_id' => string, 'error_code' => string, 'error_message' => string]
     */
    public function send(
        string $phoneNumber,
        string $templateId,
        array  $templateParams = [],
        string $tag = null
    ): array
    {
        $body = [
            'SmsAccount' => $this->config['account'] ?? '',
            'Sign' => $this->config['sign_name'] ?? '',
            'TemplateID' => $templateId,
            'TemplateParam' => json_encode($templateParams),
            'PhoneNumbers' => $phoneNumber,
        ];

        if ($tag !== null) {
            $body['Tag'] = $tag;
        }

        Log::debug('火山云短信发送请求', [
            'phone' => $this->maskPhone($phoneNumber),
            'template_id' => $templateId,
        ]);

        try {
            $response = $this->client->sendSms(['json' => $body]);

            return $this->parseResponse($response->getContents(), $phoneNumber);
        } catch (\Exception $e) {
            Log::error('火山云短信发送异常', [
                'phone' => $this->maskPhone($phoneNumber),
                'template_id' => $templateId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'request_id' => '',
                'error_code' => 'EXCEPTION',
                'error_message' => $e->getMessage(),
            ];
        }
    }

    /**
     * 批量发送短信
     *
     * @param array $phoneNumbers 手机号数组
     * @param string $templateId 模板ID
     * @param array $templateParams 模板参数
     * @return array
     */
    public function sendBatch(
        array  $phoneNumbers,
        string $templateId,
        array  $templateParams = []
    ): array
    {
        // 火山云支持逗号分隔的手机号
        $phones = implode(',', $phoneNumbers);

        return $this->send($phones, $templateId, $templateParams);
    }

    /**
     * 解析火山云响应
     *
     * @param mixed $response
     * @param string $phoneNumber
     * @return array
     */
    protected function parseResponse($response, string $phoneNumber): array
    {
        $result = [
            'success' => false,
            'request_id' => '',
            'error_code' => '',
            'error_message' => '',
        ];

        // 响应为空
        if (empty($response)) {
            $result['error_code'] = 'EMPTY_RESPONSE';
            $result['error_message'] = '响应为空';

            Log::error('火山云短信响应为空', [
                'phone' => $this->maskPhone($phoneNumber),
            ]);

            return $result;
        }

        // 解析响应
        $responseData = is_string($response) ? json_decode($response, true) : $response;

        if (json_last_error() !== JSON_ERROR_NONE) {
            $result['error_code'] = 'INVALID_JSON';
            $result['error_message'] = '响应格式错误';

            Log::error('火山云短信响应格式错误', [
                'phone' => $this->maskPhone($phoneNumber),
                'response' => $response,
            ]);

            return $result;
        }

        // 提取 RequestId
        $result['request_id'] = $responseData['ResponseMetadata']['RequestId'] ?? '';

        // 检查是否有错误
        if (isset($responseData['ResponseMetadata']['Error'])) {
            $error = $responseData['ResponseMetadata']['Error'];
            $result['error_code'] = $error['Code'] ?? 'UNKNOWN';
            $result['error_message'] = $error['Message'] ?? '未知错误';

            Log::error('火山云短信发送失败', [
                'phone' => $this->maskPhone($phoneNumber),
                'request_id' => $result['request_id'],
                'error_code' => $result['error_code'],
                'error_message' => $result['error_message'],
            ]);

            return $result;
        }

        // 检查发送结果
        $sendResult = $responseData['Result'] ?? [];
        if (!empty($sendResult['MessageID'])) {
            $result['success'] = true;
            $result['message_id'] = $sendResult['MessageID'];

            Log::info('火山云短信发送成功', [
                'phone' => $this->maskPhone($phoneNumber),
                'request_id' => $result['request_id'],
                'message_id' => $result['message_id'],
            ]);
        } else {
            $result['error_code'] = 'NO_MESSAGE_ID';
            $result['error_message'] = '发送结果异常';

            Log::warning('火山云短信发送结果异常', [
                'phone' => $this->maskPhone($phoneNumber),
                'request_id' => $result['request_id'],
                'result' => $sendResult,
            ]);
        }

        return $result;
    }

    /**
     * 手机号脱敏
     *
     * @param string $phone
     * @return string
     */
    protected function maskPhone(string $phone): string
    {
        return $phone;
        if (strlen($phone) >= 7) {
            return substr($phone, 0, 3) . '****' . substr($phone, -4);
        }

        return '****';
    }
}
