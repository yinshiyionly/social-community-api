<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\App\Member\LoginRequest;
use App\Http\Requests\App\Member\SendSmsRequest;
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
            'data' => [
                'token' => $result['token'],
            ],
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
            'data' => [
                'expireSeconds' => $result['expire_seconds'],
            ],
        ]);
    }
}
