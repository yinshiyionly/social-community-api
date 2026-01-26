<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\App\Member\LoginRequest;
use App\Http\Requests\App\Member\SendSmsRequest;
use App\Http\Requests\App\Member\SmsLoginRequest;
use App\Http\Requests\App\Member\WeChatLoginRequest;
use App\Http\Resources\App\AppApiResponse;
use App\Services\App\MemberAuthService;
use App\Services\App\SmsService;
use App\Services\App\WeChatService;
use Illuminate\Support\Facades\Log;

class MemberAuthController extends Controller
{
    /**
     * @var MemberAuthService
     */
    protected $authService;

    /**
     * @var SmsService
     */
    protected $smsService;

    /**
     * @var WeChatService
     */
    protected $weChatService;

    public function __construct(
        MemberAuthService $authService,
        SmsService        $smsService,
        WeChatService     $weChatService
    )
    {
        $this->authService = $authService;
        $this->smsService = $smsService;
        $this->weChatService = $weChatService;
    }

    /**
     * 手机号密码登录
     *
     * @param LoginRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request)
    {
        $phone = $request->input('phone');
        $password = $request->input('password');

        $result = $this->authService->loginByPhone($phone, $password);

        if (!$result) {
            return AppApiResponse::error('手机号或密码错误');
        }

        return AppApiResponse::success(['data' => [
            'token' => $result['token']
        ]]);
    }

    /**
     * 手机号验证码登录
     *
     * @param SmsLoginRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function smsLogin(SmsLoginRequest $request)
    {
        $phone = $request->input('phone');
        $code = $request->input('code');

        // 验证验证码
        if (!$this->smsService->verify($phone, $code, SmsService::SCOPE_LOGIN)) {
            return AppApiResponse::error('验证码错误或已过期');
        }

        // 登录（不存在则自动注册）
        $result = $this->authService->loginBySmsCode($phone);

        if (!$result) {
            return AppApiResponse::error('登录失败，请稍后重试');
        }

        // 账号被禁用
        if ($result['member']->isDisabled()) {
            return AppApiResponse::accountDisabled();
        }

        return AppApiResponse::success(['data' => [
            'token' => $result['token']
        ]]);
    }

    /**
     * 移动应用微信登录
     *
     * @param WeChatLoginRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function appWeChatLogin(WeChatLoginRequest $request)
    {
        $code = $request->input('code');

        // 1. 通过 code 获取 access_token
        $tokenInfo = $this->weChatService->getAccessTokenByCode($code);
        if (!$tokenInfo) {
            Log::warning('WeChat login: get access_token failed', ['code' => substr($code, 0, 10) . '...']);
            return AppApiResponse::error('微信授权失败，请重试');
        }

        // 2. 获取微信用户信息
        $userInfo = $this->weChatService->getUserInfo($tokenInfo['access_token'], $tokenInfo['openid']);
        if (!$userInfo) {
            Log::warning('WeChat login: get userinfo failed', ['openid' => $tokenInfo['openid']]);
            return AppApiResponse::error('获取用户信息失败，请重试');
        }

        // 3. 登录或注册
        $result = $this->authService->loginByWeChatApp(
            $tokenInfo['openid'],
            $tokenInfo['unionid'] ?? '',
            $userInfo,
            $tokenInfo
        );

        if (!$result) {
            return AppApiResponse::error('登录失败，请稍后重试');
        }

        // 4. 检查账号状态
        if ($result['member']->isDisabled()) {
            return AppApiResponse::accountDisabled();
        }

        // 5. 检查是否需要绑定手机号
        $needBindPhone = empty($result['member']->phone);

        return AppApiResponse::success([
            'data' => [
                'token' => $result['token'],
                'is_new' => $result['is_new'],
                'need_bind_phone' => $needBindPhone,
            ]
        ]);
    }

    /**
     * 发送登录验证码
     *
     * @param SendSmsRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendSms(SendSmsRequest $request)
    {
        $phone = $request->input('phone');

        $result = $this->smsService->sendLoginCode($phone);

        if (!$result['success']) {
            return AppApiResponse::error($result['message']);
        }

        return AppApiResponse::success(['data' => [
            'expireSeconds' => $result['expire_seconds']
        ]]);
    }
}
