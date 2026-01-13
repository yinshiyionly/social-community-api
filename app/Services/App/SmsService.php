<?php

namespace App\Services\App;

use App\Jobs\App\SendLoginSmsCodeJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 短信服务
 *
 * 提供验证码发送、存储、验证功能，支持作用域隔离
 */
class SmsService
{
    /**
     * 验证码有效期（秒）
     */
    const CODE_EXPIRE_SECONDS = 300;

    /**
     * 发送间隔（秒）
     */
    const SEND_INTERVAL_SECONDS = 60;

    /**
     * 验证码长度
     */
    const CODE_LENGTH = 6;

    /**
     * 缓存 key 前缀
     */
    const CACHE_PREFIX = 'sms:code:';
    const CACHE_INTERVAL_PREFIX = 'sms:interval:';
    const CACHE_QUOTA_PREFIX = 'sms:quota:';

    /**
     * 每日发送配额
     */
    const DAILY_QUOTA = 10;

    /**
     * 作用域常量
     */
    const SCOPE_LOGIN = 'login';
    const SCOPE_REGISTER = 'register';
    const SCOPE_RESET_PASSWORD = 'reset_password';
    const SCOPE_BIND_PHONE = 'bind_phone';

    /**
     * 发送登录验证码
     *
     * @param string $phone
     * @return array ['success' => bool, 'message' => string, 'expire_seconds' => int]
     */
    public function sendLoginCode(string $phone): array
    {
        return $this->sendCode($phone, self::SCOPE_LOGIN);
    }

    /**
     * 发送验证码（通用方法）
     *
     * @param string $phone 手机号
     * @param string $scope 作用域
     * @return array ['success' => bool, 'message' => string, 'expire_seconds' => int]
     */
    public function sendCode(string $phone, string $scope): array
    {
        // 检查每日配额
        if ($this->isQuotaExceeded($phone)) {
            return [
                'success' => false,
                'message' => '今日发送次数已达上限，请明天再试',
                'expire_seconds' => 0,
            ];
        }

        // 检查发送间隔
        if ($this->isInInterval($phone, $scope)) {
            return [
                'success' => false,
                'message' => '发送过于频繁，请稍后重试',
                'expire_seconds' => 0,
            ];
        }

        // 生成验证码
        $code = $this->generateCode();

        // 存储验证码
        $this->set($phone, $code, $scope);

        // 设置发送间隔
        $this->setInterval($phone, $scope);

        // 增加配额计数
        $this->incrementQuota($phone);

        // 异步发送短信
        $this->dispatchSmsJob($phone, $code, $scope);

        Log::info('SMS code sent', [
            'phone' => $this->maskPhone($phone),
            'scope' => $scope,
        ]);

        return [
            'success' => true,
            'message' => 'success',
            'expire_seconds' => self::CODE_EXPIRE_SECONDS,
        ];
    }

    /**
     * 设置验证码
     *
     * @param string $phone 手机号
     * @param string $code 验证码
     * @param string $scope 作用域
     * @param int $ttl 有效期（秒）
     * @return bool
     */
    public function set(string $phone, string $code, string $scope, int $ttl = self::CODE_EXPIRE_SECONDS): bool
    {
        $cacheKey = $this->buildCacheKey($phone, $scope);

        Log::debug('设置短信验证码', [
            'phone' => $this->maskPhone($phone),
            'scope' => $scope,
            'ttl' => $ttl,
        ]);

        return Cache::put($cacheKey, $code, $ttl);
    }

    /**
     * 验证验证码
     *
     * @param string $phone 手机号
     * @param string $code 验证码
     * @param string $scope 作用域
     * @param bool $forget 验证成功后是否删除，默认true
     * @return bool
     */
    public function verify(string $phone, string $code, string $scope, bool $forget = true): bool
    {
        $cacheKey = $this->buildCacheKey($phone, $scope);
        $cachedCode = Cache::get($cacheKey);

        if ($cachedCode === null) {
            Log::debug('验证码不存在或已过期', [
                'phone' => $this->maskPhone($phone),
                'scope' => $scope,
            ]);
            return false;
        }

        $isValid = $cachedCode === $code;

        if ($isValid && $forget) {
            $this->forget($phone, $scope);
        }

        Log::debug('验证码验证结果', [
            'phone' => $this->maskPhone($phone),
            'scope' => $scope,
            'is_valid' => $isValid,
        ]);

        return $isValid;
    }

    /**
     * 删除验证码
     *
     * @param string $phone 手机号
     * @param string $scope 作用域
     * @return bool
     */
    public function forget(string $phone, string $scope): bool
    {
        $cacheKey = $this->buildCacheKey($phone, $scope);
        return Cache::forget($cacheKey);
    }

    /**
     * 检查验证码是否存在
     *
     * @param string $phone 手机号
     * @param string $scope 作用域
     * @return bool
     */
    public function exists(string $phone, string $scope): bool
    {
        $cacheKey = $this->buildCacheKey($phone, $scope);
        return Cache::has($cacheKey);
    }

    /**
     * 生成验证码
     *
     * @return string
     */
    protected function generateCode(): string
    {
        $min = pow(10, self::CODE_LENGTH - 1);
        $max = pow(10, self::CODE_LENGTH) - 1;
        return (string) mt_rand($min, $max);
    }

    /**
     * 检查是否在发送间隔内
     *
     * @param string $phone
     * @param string $scope
     * @return bool
     */
    protected function isInInterval(string $phone, string $scope): bool
    {
        $cacheKey = self::CACHE_INTERVAL_PREFIX . $scope . ':' . $phone;
        return Cache::has($cacheKey);
    }

    /**
     * 设置发送间隔
     *
     * @param string $phone
     * @param string $scope
     */
    protected function setInterval(string $phone, string $scope): void
    {
        $cacheKey = self::CACHE_INTERVAL_PREFIX . $scope . ':' . $phone;
        Cache::put($cacheKey, 1, self::SEND_INTERVAL_SECONDS);
    }

    /**
     * 检查每日配额是否超限
     *
     * @param string $phone
     * @return bool
     */
    protected function isQuotaExceeded(string $phone): bool
    {
        $cacheKey = self::CACHE_QUOTA_PREFIX . $phone;
        $count = (int) Cache::get($cacheKey, 0);
        $quota = config('services.sms.daily_quota', self::DAILY_QUOTA);

        return $count >= $quota;
    }

    /**
     * 增加配额计数
     *
     * @param string $phone
     */
    protected function incrementQuota(string $phone): void
    {
        $cacheKey = self::CACHE_QUOTA_PREFIX . $phone;
        $count = (int) Cache::get($cacheKey, 0);

        // 计算到今天结束的剩余秒数
        $secondsUntilEndOfDay = strtotime('tomorrow') - time();

        Cache::put($cacheKey, $count + 1, $secondsUntilEndOfDay);
    }

    /**
     * 获取剩余配额
     *
     * @param string $phone
     * @return int
     */
    public function getRemainingQuota(string $phone): int
    {
        $cacheKey = self::CACHE_QUOTA_PREFIX . $phone;
        $count = (int) Cache::get($cacheKey, 0);
        $quota = config('services.sms.daily_quota', self::DAILY_QUOTA);

        return max(0, $quota - $count);
    }

    /**
     * 构建缓存键
     *
     * @param string $phone 手机号
     * @param string $scope 作用域
     * @return string
     */
    protected function buildCacheKey(string $phone, string $scope): string
    {
        return self::CACHE_PREFIX . $scope . ':' . $phone;
    }

    /**
     * 脱敏手机号（用于日志）
     *
     * @param string $phone 手机号
     * @return string
     */
    protected function maskPhone(string $phone): string
    {
        if (strlen($phone) >= 7) {
            return substr($phone, 0, 3) . '****' . substr($phone, -4);
        }
        return '****';
    }

    /**
     * 分发短信发送任务
     *
     * @param string $phone
     * @param string $code
     * @param string $scope
     */
    protected function dispatchSmsJob(string $phone, string $code, string $scope): void
    {
        $templateId = $this->getTemplateId($scope);

        SendLoginSmsCodeJob::dispatch([
            'phone_number' => $phone,
            'code' => $code,
            'template_id' => $templateId,
            'expire_minutes' => intval(self::CODE_EXPIRE_SECONDS / 60),
        ]);
    }

    /**
     * 根据作用域获取短信模板ID
     *
     * @param string $scope
     * @return string
     */
    protected function getTemplateId(string $scope): string
    {
        $templates = [
            self::SCOPE_LOGIN => config('services.volcengine.sms.template_login', ''),
            self::SCOPE_REGISTER => config('services.volcengine.sms.template_register', ''),
            self::SCOPE_RESET_PASSWORD => config('services.volcengine.sms.template_reset_password', ''),
            self::SCOPE_BIND_PHONE => config('services.volcengine.sms.template_bind_phone', ''),
        ];

        return $templates[$scope] ?? $templates[self::SCOPE_LOGIN];
    }
}
