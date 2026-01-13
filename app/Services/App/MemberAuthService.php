<?php

namespace App\Services\App;

use App\Helper\JwtHelper;
use App\Models\App\AppMemberBase;
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
                'phone' => substr($phone, 0, 3) . '****' . substr($phone, -4),
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
