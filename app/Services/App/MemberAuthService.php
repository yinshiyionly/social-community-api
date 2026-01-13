<?php

namespace App\Services\App;

use App\Helper\JwtHelper;
use App\Models\App\AppMemberBase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

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
