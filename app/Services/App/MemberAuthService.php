<?php

namespace App\Services\App;

use App\Helper\JwtHelper;
use App\Models\App\AppMemberBase;
use App\Models\App\AppMemberOauth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * 会员认证服务
 */
class MemberAuthService
{
    /**
     * Token 过期时间（秒）- 7天
     */
    const TOKEN_EXPIRE_SECONDS = 604800;

    /**
     * 手机号密码登录
     *
     * @param string $phone
     * @param string $password
     * @return array|null 成功返回 ['token' => '...', 'member' => AppMemberBase]，失败返回 null
     */
    public function loginByPhone(string $phone, string $password): ?array
    {
        $member = AppMemberBase::byPhone($phone)->first();

        if (!$member) {
            Log::info('Login failed: member not found', ['phone' => $phone]);
            return null;
        }

        if (!Hash::check($password, $member->password)) {
            Log::info('Login failed: password mismatch', ['phone' => $phone]);
            return null;
        }

        if ($member->isDisabled()) {
            Log::info('Login failed: account disabled', ['member_id' => $member->member_id]);
            return null;
        }

        $token = $this->generateToken($member);

        return [
            'token' => $token,
            'member' => $member,
        ];
    }

    /**
     * 手机号验证码登录（不存在则自动注册）
     *
     * @param string $phone
     * @return array|null 成功返回 ['token' => '...', 'member' => AppMemberBase, 'is_new' => bool]，失败返回 null
     */
    public function loginBySmsCode(string $phone): ?array
    {
        $member = AppMemberBase::byPhone($phone)->first();
        $isNew = false;

        // 不存在则自动注册
        if (!$member) {
            $member = $this->registerByPhone($phone);
            if (!$member) {
                Log::error('SMS login failed: auto register failed', ['phone' => $phone]);
                return null;
            }
            $isNew = true;
        }

        // 检查账号状态
        if ($member->isDisabled()) {
            Log::info('SMS login failed: account disabled', ['member_id' => $member->member_id]);
            return null;
        }

        $token = $this->generateToken($member);

        Log::info('SMS login success', [
            'member_id' => $member->member_id,
            'is_new' => $isNew,
        ]);

        return [
            'token' => $token,
            'member' => $member,
            'is_new' => $isNew,
        ];
    }

    /**
     * 通过手机号注册会员
     *
     * @param string $phone
     * @return AppMemberBase|null
     */
    protected function registerByPhone(string $phone): ?AppMemberBase
    {
        try {
            $member = AppMemberBase::create([
                'phone' => $phone,
                'nickname' => $this->generateNickname(),
                'status' => AppMemberBase::STATUS_NORMAL,
                'invite_code' => $this->generateInviteCode(),
            ]);

            Log::info('Member registered by phone', [
                'member_id' => $member->member_id,
                'phone' => $phone
            ]);

            return $member;
        } catch (\Exception $e) {
            Log::error('Member register failed', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * 生成随机昵称
     *
     * @return string
     */
    protected function generateNickname(): string
    {
        $prefixes = ['快乐', '阳光', '星空', '微风', '清晨', '夏日', '秋叶', '冬雪'];
        $suffixes = ['小猫', '小狗', '小鸟', '小鱼', '小熊', '小兔', '小鹿', '小象'];

        $prefix = $prefixes[array_rand($prefixes)];
        $suffix = $suffixes[array_rand($suffixes)];
        $number = mt_rand(100, 999);

        return $prefix . $suffix . $number;
    }

    /**
     * 生成邀请码
     *
     * @return string
     */
    protected function generateInviteCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (AppMemberBase::byInviteCode($code)->exists());

        return $code;
    }

    /**
     * 微信 APP 登录
     *
     * @param string $openid 微信 openid
     * @param string $unionid 微信 unionid
     * @param array $userInfo 微信用户信息
     * @param array $tokenInfo access_token 等信息
     * @return array|null 成功返回 ['token' => '...', 'member' => AppMemberBase, 'is_new' => bool]
     */
    public function loginByWeChatApp(string $openid, string $unionid, array $userInfo, array $tokenInfo): ?array
    {
        $platform = AppMemberOauth::PLATFORM_WECHAT_APP;

        try {
            return DB::transaction(function () use ($openid, $unionid, $userInfo, $tokenInfo, $platform) {
                $oauth = null;
                $member = null;
                $isNew = false;

                // 1. 优先通过 unionid 查找（可关联多端账号）
                if (!empty($unionid)) {
                    $oauth = AppMemberOauth::byUnionid($unionid)->first();
                }

                // 2. 如果没找到，通过 platform + openid 查找
                if (!$oauth) {
                    $oauth = AppMemberOauth::byPlatformAndOpenid($platform, $openid)->first();
                }

                // 3. 已存在绑定关系
                if ($oauth) {
                    $member = $oauth->member;

                    // 更新 OAuth 信息
                    $this->updateOauthInfo($oauth, $userInfo, $tokenInfo);

                    Log::info('WeChat APP login: existing user', [
                        'member_id' => $member->member_id,
                        'openid' => $openid,
                    ]);
                } else {
                    // 4. 新用户，创建会员和 OAuth 记录
                    $member = $this->createMemberFromWeChat($userInfo);
                    if (!$member) {
                        return null;
                    }

                    $oauth = $this->createOauthRecord($member, $platform, $openid, $unionid, $userInfo, $tokenInfo);
                    if (!$oauth) {
                        throw new \Exception('Failed to create oauth record');
                    }

                    $isNew = true;

                    Log::info('WeChat APP login: new user created', [
                        'member_id' => $member->member_id,
                        'openid' => $openid,
                    ]);
                }

                // 检查账号状态
                if ($member->isDisabled()) {
                    Log::info('WeChat APP login failed: account disabled', [
                        'member_id' => $member->member_id,
                    ]);
                    return null;
                }

                $token = $this->generateToken($member);

                return [
                    'token' => $token,
                    'member' => $member,
                    'is_new' => $isNew,
                ];
            });
        } catch (\Exception $e) {
            Log::error('WeChat APP login failed', [
                'openid' => $openid,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * 通过微信信息创建会员
     *
     * @param array $userInfo
     * @return AppMemberBase|null
     */
    protected function createMemberFromWeChat(array $userInfo): ?AppMemberBase
    {
        try {
            $nickname = !empty($userInfo['nickname']) ? $userInfo['nickname'] : $this->generateNickname();
            $avatar = $userInfo['headimgurl'] ?? '';
            $gender = $userInfo['sex'] ?? AppMemberBase::GENDER_UNKNOWN;

            $member = AppMemberBase::create([
                'nickname' => $nickname,
                'avatar' => $avatar,
                'gender' => $gender,
                'status' => AppMemberBase::STATUS_NORMAL,
                'invite_code' => $this->generateInviteCode(),
            ]);

            Log::info('Member created from WeChat', [
                'member_id' => $member->member_id,
                'nickname' => $nickname,
            ]);

            return $member;
        } catch (\Exception $e) {
            Log::error('Create member from WeChat failed', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * 创建 OAuth 记录
     *
     * @param AppMemberBase $member
     * @param string $platform
     * @param string $openid
     * @param string $unionid
     * @param array $userInfo
     * @param array $tokenInfo
     * @return AppMemberOauth|null
     */
    protected function createOauthRecord(
        AppMemberBase $member,
        string        $platform,
        string        $openid,
        string        $unionid,
        array         $userInfo,
        array         $tokenInfo
    ): ?AppMemberOauth
    {
        try {
            $expiresAt = null;
            if (!empty($tokenInfo['expires_in'])) {
                $expiresAt = now()->addSeconds($tokenInfo['expires_in']);
            }

            return AppMemberOauth::create([
                'member_id' => $member->member_id,
                'platform' => $platform,
                'openid' => $openid,
                'unionid' => $unionid,
                'nickname' => $userInfo['nickname'] ?? '',
                'avatar' => $userInfo['headimgurl'] ?? '',
                'gender' => $userInfo['sex'] ?? 0,
                'country' => $userInfo['country'] ?? '',
                'province' => $userInfo['province'] ?? '',
                'city' => $userInfo['city'] ?? '',
                'raw_data' => $userInfo,
                'access_token' => $tokenInfo['access_token'] ?? '',
                'refresh_token' => $tokenInfo['refresh_token'] ?? '',
                'token_expires_at' => $expiresAt,
            ]);
        } catch (\Exception $e) {
            Log::error('Create oauth record failed', [
                'member_id' => $member->member_id,
                'platform' => $platform,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * 更新 OAuth 信息
     *
     * @param AppMemberOauth $oauth
     * @param array $userInfo
     * @param array $tokenInfo
     * @return void
     */
    protected function updateOauthInfo(AppMemberOauth $oauth, array $userInfo, array $tokenInfo): void
    {
        try {
            $updateData = [
                'raw_data' => $userInfo,
            ];

            // 更新用户信息（如果有）
            if (!empty($userInfo['nickname'])) {
                $updateData['nickname'] = $userInfo['nickname'];
            }
            if (!empty($userInfo['headimgurl'])) {
                $updateData['avatar'] = $userInfo['headimgurl'];
            }
            if (isset($userInfo['sex'])) {
                $updateData['gender'] = $userInfo['sex'];
            }
            if (!empty($userInfo['country'])) {
                $updateData['country'] = $userInfo['country'];
            }
            if (!empty($userInfo['province'])) {
                $updateData['province'] = $userInfo['province'];
            }
            if (!empty($userInfo['city'])) {
                $updateData['city'] = $userInfo['city'];
            }

            // 更新 token 信息
            if (!empty($tokenInfo['access_token'])) {
                $updateData['access_token'] = $tokenInfo['access_token'];
            }
            if (!empty($tokenInfo['refresh_token'])) {
                $updateData['refresh_token'] = $tokenInfo['refresh_token'];
            }
            if (!empty($tokenInfo['expires_in'])) {
                $updateData['token_expires_at'] = now()->addSeconds($tokenInfo['expires_in']);
            }

            $oauth->update($updateData);
        } catch (\Exception $e) {
            Log::warning('Update oauth info failed', [
                'oauth_id' => $oauth->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 生成 JWT Token
     *
     * @param AppMemberBase $member
     * @return string
     */
    public function generateToken(AppMemberBase $member): string
    {
        $payload = [
            'member_id' => $member->member_id,
            'user_type' => 'app',
            'nickname' => $member->nickname,
            'iss' => 'app',
        ];

        $secret = config('app.jwt_app_secret', config('app.key'));

        return JwtHelper::encode($payload, $secret, self::TOKEN_EXPIRE_SECONDS);
    }
}
