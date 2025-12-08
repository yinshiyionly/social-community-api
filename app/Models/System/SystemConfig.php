<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemConfig extends Model
{
    protected $table = 'sys_config';
    protected $primaryKey = 'config_id';
    public $timestamps = false;

    protected $fillable = [
        'config_name', 'config_key', 'config_value', 'config_type',
        'create_by', 'create_time', 'update_by', 'update_time', 'remark'
    ];

    protected $casts = [
        'create_time' => 'datetime',
        'update_time' => 'datetime',
    ];

    // 缓存键前缀
    const CACHE_PREFIX = 'sys_config:';

    /**
     * 根据键名获取配置值
     */
    public static function getConfigByKey($configKey, $default = null)
    {
        $cacheKey = self::CACHE_PREFIX . $configKey;

        return Cache::remember($cacheKey, 3600, function () use ($configKey, $default) {
            $config = self::where('config_key', $configKey)->first();
            return $config ? $config->config_value : $default;
        });
    }

    /**
     * 设置配置值
     */
    public static function setConfig($configKey, $configValue)
    {
        $config = self::where('config_key', $configKey)->first();

        if ($config) {
            $config->update([
                'config_value' => $configValue,
                'update_time' => now()
            ]);
        } else {
            self::create([
                'config_key' => $configKey,
                'config_value' => $configValue,
                'config_name' => $configKey,
                'config_type' => 'N',
                'create_time' => now()
            ]);
        }

        // 清除缓存
        Cache::forget(self::CACHE_PREFIX . $configKey);
    }

    /**
     * 清除配置缓存
     */
    public static function clearConfigCache($configKey = null)
    {
        if ($configKey) {
            Cache::forget(self::CACHE_PREFIX . $configKey);
        } else {
            // 清除所有配置缓存
            $configs = self::all();
            foreach ($configs as $config) {
                Cache::forget(self::CACHE_PREFIX . $config->config_key);
            }
        }
    }

    /**
     * 模型事件
     */
    protected static function boot()
    {
        parent::boot();

        // 更新或删除时清除缓存
        static::updated(function ($config) {
            Cache::forget(self::CACHE_PREFIX . $config->config_key);
        });

        static::deleted(function ($config) {
            Cache::forget(self::CACHE_PREFIX . $config->config_key);
        });
    }
}
