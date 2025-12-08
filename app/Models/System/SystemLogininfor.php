<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class SystemLogininfor extends Model
{
    protected $table = 'sys_logininfor';
    protected $primaryKey = 'info_id';
    public $timestamps = false;

    protected $fillable = [
        'user_name', 'ipaddr', 'login_location', 'browser', 'os',
        'status', 'msg', 'login_time'
    ];

    protected $casts = [
        'login_time' => 'datetime',
    ];

    // 登录状态常量
    const STATUS_SUCCESS = '0';  // 成功
    const STATUS_FAIL = '1';     // 失败

    /**
     * 获取状态文本
     */
    public function getStatusTextAttribute()
    {
        $statuses = [
            self::STATUS_SUCCESS => '成功',
            self::STATUS_FAIL => '失败'
        ];

        return $statuses[$this->status] ?? '未知';
    }

    /**
     * 记录登录信息
     */
    public static function recordLoginInfo($data)
    {
        return self::create(array_merge([
            'login_time' => now(),
            'status' => self::STATUS_SUCCESS
        ], $data));
    }

    /**
     * 记录登录成功
     */
    public static function recordLoginSuccess($userName, $request)
    {
        return self::recordLoginInfo([
            'user_name' => $userName,
            'ipaddr' => $request->ip(),
            'login_location' => self::getLocationByIp($request->ip()),
            'browser' => self::getBrowser($request->userAgent()),
            'os' => self::getOs($request->userAgent()),
            'status' => self::STATUS_SUCCESS,
            'msg' => '登录成功'
        ]);
    }

    /**
     * 记录登录失败
     */
    public static function recordLoginFail($userName, $request, $message)
    {
        return self::recordLoginInfo([
            'user_name' => $userName,
            'ipaddr' => $request->ip(),
            'login_location' => self::getLocationByIp($request->ip()),
            'browser' => self::getBrowser($request->userAgent()),
            'os' => self::getOs($request->userAgent()),
            'status' => self::STATUS_FAIL,
            'msg' => $message
        ]);
    }

    /**
     * 根据IP获取地理位置（简化实现）
     */
    private static function getLocationByIp($ip)
    {
        // 这里可以集成第三方IP定位服务
        if ($ip === '127.0.0.1' || $ip === '::1') {
            return '内网IP';
        }
        // 获取IP地理位置信息
        try {
            if (function_exists('ip2region')) {
                $location = ip2region($ip);
                return self::extractProvinceCity($location);
            }
        } catch (\Exception $e) {
            Log::channel('daily')->error('根据IP获取地理位置失败', [
                'ip' => $ip,
                'msg' => $e->getMessage()
            ]);
            return '未知位置';
        }
        return '未知位置';
    }

    /**
     * 解析浏览器信息
     */
    private static function getBrowser($userAgent)
    {
        if (strpos($userAgent, 'Chrome') !== false) {
            return 'Chrome';
        } elseif (strpos($userAgent, 'Firefox') !== false) {
            return 'Firefox';
        } elseif (strpos($userAgent, 'Safari') !== false) {
            return 'Safari';
        } elseif (strpos($userAgent, 'Edge') !== false) {
            return 'Edge';
        }
        return '未知浏览器';
    }

    /**
     * 解析操作系统信息
     */
    private static function getOs($userAgent)
    {
        if (strpos($userAgent, 'Windows') !== false) {
            return 'Windows';
        } elseif (strpos($userAgent, 'Mac') !== false) {
            return 'Mac OS';
        } elseif (strpos($userAgent, 'Linux') !== false) {
            return 'Linux';
        } elseif (strpos($userAgent, 'Android') !== false) {
            return 'Android';
        } elseif (strpos($userAgent, 'iOS') !== false) {
            return 'iOS';
        }
        return '未知系统';
    }

    /**
     * 清理过期登录日志
     */
    public static function cleanExpiredLogs($days = 30)
    {
        $expiredDate = now()->subDays($days);
        return self::where('login_time', '<', $expiredDate)->delete();
    }

    /**
     * 提取IP地理位置中的省市信息
     *
     * @param string $location ip2region返回的完整位置信息
     * @return string 提取的省市信息
     */
    public static function extractProvinceCity($location)
    {
        if (empty($location)) {
            return '';
        }

        // 移除国家前缀"中国"
        $location = preg_replace('/^中国/', '', $location);

        // 移除网络运营商信息（括号内的内容）
        $location = preg_replace('/【[^】]*】/', '', $location);

        // 移除"|"分隔符
        $location = str_replace('|', '', $location);

        return trim($location);
    }
}
