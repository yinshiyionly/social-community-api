<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\App\Member\LoginRequest;
use App\Http\Requests\App\Member\SendSmsRequest;
use App\Http\Requests\App\Member\SmsLoginRequest;
use App\Http\Resources\App\AppApiResponse;
use App\Services\App\MemberAuthService;
use App\Services\App\SmsService;

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

    public function __construct(MemberAuthService $authService, SmsService $smsService)
    {
        $this->authService = $authService;
        $this->smsService = $smsService;
    }

    /**
     * 手机号密码登录
     */
    public function login(LoginRequest $request)
    {
        $phone = $request->input('phone');
        $password = $request->input('password');

        $result = $this->authService->loginByPhone($phone, $password);

        if (!$result) {
            return AppApiResponse::error('手机号或密码错误');
        }

        return AppApiResponse::success([
            'token' => $result['token'],
        ]);
    }

    /**
     * 手机号验证码登录
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

        return AppApiResponse::success([
            'token' => $result['token'],
        ]);
    }

    /**
     * 发送登录验证码
     */
    public function sendSms(SendSmsRequest $request)
    {
        $phone = $request->input('phone');

        $result = $this->smsService->sendLoginCode($phone);

        if (!$result['success']) {
            return AppApiResponse::error($result['message']);
        }

        return AppApiResponse::success([
            'expireSeconds' => $result['expire_seconds'],
        ]);
    }
}
