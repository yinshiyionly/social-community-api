<?php

namespace App\Services\App;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * 微信服务类
 */
class WeChatService
{
    /**
     * 微信 API 基础地址
     */
    const API_BASE_URL = 'https://api.weixin.qq.com';

    /**
     * 通过 code 获取 access_token（移动应用）
     *
     * @param string $code 授权码
     * @return array|null 成功返回 ['access_token', 'openid', 'unionid', 'refresh_token', 'expires_in']
     */
    public function getAccessTokenByCode(string $code): ?array
    {
        $appId = config('services.wechat.app.app_id');
        $appSecret = config('services.wechat.app.app_secret');

        if (empty($appId) || empty($appSecret)) {
            Log::error('WeChat APP config missing', [
                'has_app_id' => !empty($appId),
                'has_app_secret' => !empty($appSecret),
            ]);
            return null;
        }

        $url = self::API_BASE_URL . '/sns/oauth2/access_token';

        try {
            $response = Http::timeout(10)->get($url, [
                'appid' => $appId,
                'secret' => $appSecret,
                'code' => $code,
                'grant_type' => 'authorization_code',
            ]);

            $data = $response->json();

            if (isset($data['errcode']) && $data['errcode'] !== 0) {
                Log::warning('WeChat get access_token failed', [
                    'errcode' => $data['errcode'],
                    'errmsg' => $data['errmsg'] ?? 'unknown',
                ]);
                return null;
            }

            Log::info('WeChat get access_token success', [
                'openid' => $data['openid'] ?? '',
                'has_unionid' => isset($data['unionid']),
            ]);

            return [
                'access_token' => $data['access_token'],
                'openid' => $data['openid'],
                'unionid' => $data['unionid'] ?? '',
                'refresh_token' => $data['refresh_token'] ?? '',
                'expires_in' => $data['expires_in'] ?? 7200,
                'scope' => $data['scope'] ?? '',
            ];
        } catch (\Exception $e) {
            Log::error('WeChat get access_token exception', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * 获取微信用户信息
     *
     * @param string $accessToken
     * @param string $openid
     * @return array|null
     */
    public function getUserInfo(string $accessToken, string $openid): ?array
    {
        $url = self::API_BASE_URL . '/sns/userinfo';

        try {
            $response = Http::timeout(10)->get($url, [
                'access_token' => $accessToken,
                'openid' => $openid,
                'lang' => 'zh_CN',
            ]);

            $data = $response->json();

            if (isset($data['errcode']) && $data['errcode'] !== 0) {
                Log::warning('WeChat get userinfo failed', [
                    'errcode' => $data['errcode'],
                    'errmsg' => $data['errmsg'] ?? 'unknown',
                ]);
                return null;
            }

            Log::info('WeChat get userinfo success', [
                'openid' => $data['openid'] ?? '',
                'nickname' => $data['nickname'] ?? '',
            ]);

            return [
                'openid' => $data['openid'],
                'unionid' => $data['unionid'] ?? '',
                'nickname' => $data['nickname'] ?? '',
                'sex' => $data['sex'] ?? 0,
                'province' => $data['province'] ?? '',
                'city' => $data['city'] ?? '',
                'country' => $data['country'] ?? '',
                'headimgurl' => $data['headimgurl'] ?? '',
                'privilege' => $data['privilege'] ?? [],
            ];
        } catch (\Exception $e) {
            Log::error('WeChat get userinfo exception', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * 刷新 access_token
     *
     * @param string $refreshToken
     * @return array|null
     */
    public function refreshAccessToken(string $refreshToken): ?array
    {
        $appId = config('services.wechat.app.app_id');

        $url = self::API_BASE_URL . '/sns/oauth2/refresh_token';

        try {
            $response = Http::timeout(10)->get($url, [
                'appid' => $appId,
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
            ]);

            $data = $response->json();

            if (isset($data['errcode']) && $data['errcode'] !== 0) {
                Log::warning('WeChat refresh token failed', [
                    'errcode' => $data['errcode'],
                    'errmsg' => $data['errmsg'] ?? 'unknown',
                ]);
                return null;
            }

            return [
                'access_token' => $data['access_token'],
                'openid' => $data['openid'],
                'refresh_token' => $data['refresh_token'] ?? '',
                'expires_in' => $data['expires_in'] ?? 7200,
                'scope' => $data['scope'] ?? '',
            ];
        } catch (\Exception $e) {
            Log::error('WeChat refresh token exception', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * 检验 access_token 是否有效
     *
     * @param string $accessToken
     * @param string $openid
     * @return bool
     */
    public function checkAccessToken(string $accessToken, string $openid): bool
    {
        $url = self::API_BASE_URL . '/sns/auth';

        try {
            $response = Http::timeout(5)->get($url, [
                'access_token' => $accessToken,
                'openid' => $openid,
            ]);

            $data = $response->json();

            return isset($data['errcode']) && $data['errcode'] === 0;
        } catch (\Exception $e) {
            return false;
        }
    }
}
