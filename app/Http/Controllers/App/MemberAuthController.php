<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\App\Member\LoginRequest;
use App\Http\Resources\App\AppApiResponse;
use App\Services\App\MemberAuthService;

class MemberAuthController extends Controller
{
    /**
     * @var MemberAuthService
     */
    protected $authService;

    public function __construct(MemberAuthService $authService)
    {
        $this->authService = $authService;
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
}
