<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\App\Member\BindPhoneRequest;
use App\Http\Requests\App\Member\LoginRequest;
use App\Http\Requests\App\Member\SendSmsRequest;
use App\Http\Requests\App\Member\SmsLoginRequest;
use App\Http\Requests\App\Member\WeChatLoginRequest;
use App\Http\Resources\App\AppApiResponse;
use App\Jobs\App\SendWelcomeMessageJob;
use App\Services\App\MemberAuthService;
use App\Services\App\SmsService;
use App\Services\App\WeChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * App 端会员认证控制器。
 *
 * 职责：
 * 1. 处理手机号/短信/微信登录入口；
 * 2. 处理绑定手机号、退出登录等认证相关接口；
 * 3. 协调短信服务、微信服务与会员认证服务，统一输出 AppApiResponse。
 */
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

    // 白名单手机号
    protected $whiteList = [
        '15201064085',
        '13888888888',
        '18701433585'
    ];

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

        if (!in_array($phone, $this->whiteList)) {
            // 验证验证码
            if (!$this->smsService->verify($phone, $code, SmsService::SCOPE_LOGIN)) {
                return AppApiResponse::error('验证码错误或已过期');
            }
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

        // 首次注册，发送欢迎消息
        if (!empty($result['is_new'])) {
            SendWelcomeMessageJob::dispatch($result['member']->member_id);
        }

        return AppApiResponse::success(['data' => [
            'token' => $result['token']
        ]]);
    }

    /**
     * 移动应用微信登录。
     *
     * 返回规则：
     * 1. 已绑定手机号：返回正式登录 token（7天）；
     * 2. 未绑定手机号：返回临时 token（10分钟），仅用于调用绑定手机号接口；
     * 3. 首次注册用户额外返回 isNew=true。
     *
     * @param WeChatLoginRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function appWeChatLogin(WeChatLoginRequest $request)
    {
        $code = $request->input('code');

        // 1. 通过 code 获取 access_token
        $tokenInfo = $this->weChatService->getAccessTokenByCode($code);
        Log::info('微信登录-tokenInfo', ['tokenInfo' => $tokenInfo]);
        /*$tokenInfo = [
            'access_token' => 'ACCESS_TOKEN',
            'expires_in' => 7200,
            'refresh_token' => 'REFRESH_TOKEN',
            'openid' => 'OPENID',
            'scope' => 'SCOPE'
        ];*/
        if (!$tokenInfo) {
            Log::warning('WeChat login: get access_token failed', ['code' => substr($code, 0, 10) . '...']);
            return AppApiResponse::error('微信授权失败，请重试');
        }

        // 2. 获取微信用户信息
        // @see https://developers.weixin.qq.com/doc/oplatform/Mobile_App/WeChat_Login/Authorized_API_call_UnionID.html
        $userInfo = $this->weChatService->getUserInfo($tokenInfo['access_token'], $tokenInfo['openid']);
        Log::info('微信登录-userInfo', ['userInfo' => $userInfo]);
        /*$userInfo = [
            'openid' => 'OPENID',
            'nickname' => 'NICKNAME',
            'sex' => 1,
            'province' => 'PROVINCE',
            'city' => 'CITY',
            'country' => 'COUNTRY',
            'headimgurl' => 'https://thirdwx.qlogo.cn/mmopen/g3MonUZtNHkdmzicIlibx6iaFqAc56vxLSUfpb6n5WKSYVY0ChQKkiaJSgQ1dZuTOgvLLrhJbERQQ4eMsv84eavHiaiceqxibJxCfHe/0',
            'privilege' => ['PRIVILEGE1', 'PRIVILEGE2'],
            'unionid' => 'o6_bmasdasdsad6_2sgVt7hMZOPfL'
        ];*/
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

        // 5. 首次注册，发送欢迎消息
        if (!empty($result['is_new'])) {
            SendWelcomeMessageJob::dispatch($result['member']->member_id);
        }

        // 6. 检查是否需要绑定手机号
        $needBindPhone = empty($result['member']->phone);

        // 7. 未绑定手机号时签发短期 token，限制其仅用于绑定手机号流程。
        // 已绑定手机号的用户继续返回正式 token，保持原登录态语义不变。
        $token = $needBindPhone
            ? $this->authService->generateBindPhoneToken($result['member'])
            : $result['token'];

        $data = [
            'token' => $token,
            'isNew' => $result['is_new'],
            'needBindPhone' => $needBindPhone
        ];

        return AppApiResponse::success([
            'data' => $data
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
        $scope = $request->input('scope', 'login');

        if ($scope == 'login') {
            $result = $this->smsService->sendLoginCode($phone);
        } else {
            $result = $this->smsService->sendBindPhoneCode($phone);
        }

        if (!$result['success']) {
            return AppApiResponse::error($result['message']);
        }

        return AppApiResponse::success(['data' => [
            'expireSeconds' => $result['expire_seconds']
        ]]);
    }

    /**
     * 绑定手机号。
     *
     * 约束：
     * 1. 需携带登录 token（正式 token 或绑定流程临时 token）；
     * 2. 绑定成功后返回正式登录 token，供客户端替换临时 token；
     * 3. 验证码失败或手机号冲突时返回业务错误，不签发 token。
     *
     * @param BindPhoneRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bindPhone(BindPhoneRequest $request)
    {
        $memberId = (int)$request->attributes->get('member_id');
        $phone = $request->input('phone');
        $code = $request->input('code');

        if (!in_array($phone, $this->whiteList)) {
            // 验证绑定手机号验证码
            if (!$this->smsService->verify($phone, $code, SmsService::SCOPE_BIND_PHONE)) {
                return AppApiResponse::error('验证码错误或已过期');
            }
        }

        $result = $this->authService->bindPhone($memberId, $phone);

        if (!$result['success']) {
            return AppApiResponse::error($result['message']);
        }

        $token = $this->authService->generateToken($result['member']);

        return AppApiResponse::success([
            'data' => [
                'token' => $token
            ]
        ]);
    }

    /**
     * 退出登录
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $memberId = $request->attributes->get('member_id');

        Log::info('Member logout', ['member_id' => $memberId]);

        return AppApiResponse::success();
    }
}
