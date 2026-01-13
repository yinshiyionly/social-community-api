<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

/**
 * 火山云短信服务
 *
 * 无状态服务类，可在 Job、Controller 等场景中直接使用
 * 所有配置从 config/services.php 读取
 */
class VolcengineSmsService
{
    private const SERVICE = 'sms';
    private const VERSION = '2021-01-11';
    private const ALGORITHM = 'HMAC-SHA256';

    /**
     * 发送短信
     *
     * @param string|array $phoneNumbers 手机号码，支持单个或多个
     * @param string $templateId 短信模板ID
     * @param array $templateParams 模板参数
     * @param string|null $signName 签名名称，不传则使用配置默认值
     * @return array
     */
    public function send(
        $phoneNumbers,
        string $templateId,
        array $templateParams = [],
        ?string $signName = null
    ): array {
        $phoneNumbers = is_array($phoneNumbers) ? $phoneNumbers : [$phoneNumbers];
        $signName = $signName ?? config('services.volcengine_sms.sign_name');

        $body = [
            'SmsAccount' => config('services.volcengine_sms.account'),
            'Sign' => $signName,
            'TemplateID' => $templateId,
            'TemplateParam' => json_encode($templateParams, JSON_UNESCAPED_UNICODE),
            'PhoneNumbers' => implode(',', $phoneNumbers),
        ];

        return $this->request('SendSms', $body);
    }

    /**
     * 批量发送短信（不同内容）
     *
     * @param array $messages 消息列表，每项包含 phone, template_id, params
     * @param string|null $signName 签名名称
     * @return array
     */
    public function sendBatch(array $messages, ?string $signName = null): array
    {
        $signName = $signName ?? config('services.volcengine_sms.sign_name');

        $body = [
            'SmsAccount' => config('services.volcengine_sms.account'),
            'Sign' => $signName,
            'Messages' => array_map(function ($msg) {
                return [
                    'TemplateID' => $msg['template_id'],
                    'TemplateParam' => json_encode($msg['params'] ?? [], JSON_UNESCAPED_UNICODE),
                    'PhoneNumbers' => is_array($msg['phone']) ? implode(',', $msg['phone']) : $msg['phone'],
                ];
            }, $messages),
        ];

        return $this->request('SendBatchSms', $body);
    }

    /**
     * 发送验证码短信
     *
     * @param string $phoneNumber 手机号码
     * @param string $code 验证码
     * @param string $templateId 模板ID
     * @param int $expireMinutes 过期时间（分钟）
     * @return array
     */
    public function sendVerificationCode(
        string $phoneNumber,
        string $code,
        string $templateId,
        int $expireMinutes = 5
    ): array {
        return $this->send($phoneNumber, $templateId, [
            'code' => $code,
            'expire' => (string) $expireMinutes,
        ]);
    }

    /**
     * 发起 API 请求
     */
    protected function request(string $action, array $body): array
    {
        $config = config('services.volcengine_sms');
        $endpoint = $config['endpoint'];
        $region = $config['region'];
        $accessKey = $config['access_key'];
        $secretKey = $config['secret_key'];

        $now = gmdate('Ymd\THis\Z');
        $date = substr($now, 0, 8);

        $query = [
            'Action' => $action,
            'Version' => self::VERSION,
        ];

        $bodyJson = json_encode($body, JSON_UNESCAPED_UNICODE);
        $headers = $this->buildHeaders($endpoint, $now, $date, $region, $accessKey, $secretKey, $query, $bodyJson);

        $url = 'https://' . $endpoint . '/?' . http_build_query($query);

        try {
            $response = Http::withHeaders($headers)
                ->timeout(30)
                ->withBody($bodyJson, 'application/json')
                ->post($url);

            $result = $response->json();

            if ($response->successful() && isset($result['ResponseMetadata']['Error'])) {
                Log::error('Volcengine SMS API error', [
                    'action' => $action,
                    'error' => $result['ResponseMetadata']['Error'],
                    'request_id' => $result['ResponseMetadata']['RequestId'] ?? null,
                ]);

                return [
                    'success' => false,
                    'error_code' => $result['ResponseMetadata']['Error']['Code'] ?? 'Unknown',
                    'error_message' => $result['ResponseMetadata']['Error']['Message'] ?? 'Unknown error',
                    'request_id' => $result['ResponseMetadata']['RequestId'] ?? null,
                ];
            }

            if (!$response->successful()) {
                Log::error('Volcengine SMS HTTP error', [
                    'action' => $action,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'error_code' => 'HTTP_ERROR',
                    'error_message' => 'HTTP request failed with status ' . $response->status(),
                ];
            }

            Log::info('Volcengine SMS sent successfully', [
                'action' => $action,
                'request_id' => $result['ResponseMetadata']['RequestId'] ?? null,
            ]);

            return [
                'success' => true,
                'data' => $result['Result'] ?? [],
                'request_id' => $result['ResponseMetadata']['RequestId'] ?? null,
            ];

        } catch (\Exception $e) {
            Log::error('Volcengine SMS exception', [
                'action' => $action,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error_code' => 'EXCEPTION',
                'error_message' => $e->getMessage(),
            ];
        }
    }

    /**
     * 构建请求头（包含签名）
     */
    protected function buildHeaders(
        string $endpoint,
        string $now,
        string $date,
        string $region,
        string $accessKey,
        string $secretKey,
        array $query,
        string $body
    ): array {
        $contentType = 'application/json';
        $bodyHash = hash('sha256', $body);

        $headers = [
            'Host' => $endpoint,
            'Content-Type' => $contentType,
            'X-Date' => $now,
            'X-Content-Sha256' => $bodyHash,
        ];

        // 构建规范请求
        $signedHeaders = 'content-type;host;x-content-sha256;x-date';
        $canonicalHeaders = "content-type:{$contentType}\nhost:{$endpoint}\nx-content-sha256:{$bodyHash}\nx-date:{$now}\n";

        ksort($query);
        $canonicalQueryString = http_build_query($query);

        $canonicalRequest = implode("\n", [
            'POST',
            '/',
            $canonicalQueryString,
            $canonicalHeaders,
            $signedHeaders,
            $bodyHash,
        ]);

        // 构建待签名字符串
        $credentialScope = "{$date}/{$region}/" . self::SERVICE . "/request";
        $stringToSign = implode("\n", [
            self::ALGORITHM,
            $now,
            $credentialScope,
            hash('sha256', $canonicalRequest),
        ]);

        // 计算签名
        $kDate = hash_hmac('sha256', $date, $secretKey, true);
        $kRegion = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', self::SERVICE, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'request', $kService, true);
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        // 构建 Authorization 头
        $headers['Authorization'] = sprintf(
            '%s Credential=%s/%s, SignedHeaders=%s, Signature=%s',
            self::ALGORITHM,
            $accessKey,
            $credentialScope,
            $signedHeaders,
            $signature
        );

        return $headers;
    }
}
