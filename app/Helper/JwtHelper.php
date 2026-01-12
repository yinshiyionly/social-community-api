<?php

namespace App\Helper;

/**
 * JWT 工具类
 * 基于 PHP 7.4 原生实现，无需额外依赖
 */
class JwtHelper
{
    /**
     * 生成 JWT Token
     *
     * @param array $payload 载荷数据
     * @param string $secret 密钥
     * @param int $expireSeconds 过期时间（秒）
     * @return string
     */
    public static function encode(array $payload, string $secret, int $expireSeconds = 86400): string
    {
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256'
        ];

        $payload['iat'] = time();
        $payload['exp'] = time() + $expireSeconds;

        $headerEncoded = self::base64UrlEncode(json_encode($header));
        $payloadEncoded = self::base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", $secret, true);
        $signatureEncoded = self::base64UrlEncode($signature);

        return "$headerEncoded.$payloadEncoded.$signatureEncoded";
    }

    /**
     * 解码并验证 JWT Token
     *
     * @param string $token
     * @param string $secret
     * @return array|null 成功返回 payload，失败返回 null
     */
    public static function decode(string $token, string $secret): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;

        // 验证签名
        $expectedSignature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", $secret, true);
        $expectedSignatureEncoded = self::base64UrlEncode($expectedSignature);

        if (!hash_equals($expectedSignatureEncoded, $signatureEncoded)) {
            return null;
        }

        $payload = json_decode(self::base64UrlDecode($payloadEncoded), true);
        if (!$payload) {
            return null;
        }

        return $payload;
    }

    /**
     * 验证 Token 是否过期
     *
     * @param array $payload
     * @return bool true 表示已过期
     */
    public static function isExpired(array $payload): bool
    {
        return !isset($payload['exp']) || $payload['exp'] < time();
    }

    /**
     * Base64 URL 安全编码
     */
    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64 URL 安全解码
     */
    private static function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
