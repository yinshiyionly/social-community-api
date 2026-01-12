<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 广告位表
 */
class AppAdSpace extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'app_ad_space';

    protected $primaryKey = 'space_id';

    protected $fillable = [
        'space_name',
        'space_code',
        'platform',
        'width',
        'height',
        'max_ads',
        'status',
    ];

    protected $casts = [
        'space_id' => 'integer',
        'platform' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'max_ads' => 'integer',
        'status' => 'integer',
    ];

    // 平台常量
    const PLATFORM_ALL = 0;
    const PLATFORM_IOS = 1;
    const PLATFORM_ANDROID = 2;

    // 状态常量
    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 2;

    /**
     * 关联广告内容
     */
    public function adItems()
    {
        return $this->hasMany(AppAdItem::class, 'space_id', 'space_id');
    }

    /**
     * 查询作用域 - 启用状态
     */
    public function scopeEnabled($query)
    {
        return $query->where('status', self::STATUS_ENABLED);
    }

    /**
     * 查询作用域 - 按平台筛选
     */
    public function scopeByPlatform($query, int $platform)
    {
        return $query->where(function ($q) use ($platform) {
            $q->where('platform', self::PLATFORM_ALL)
              ->orWhere('platform', $platform);
        });
    }

    /**
     * 查询作用域 - 按广告位code筛选
     */
    public function scopeByCode($query, string $code)
    {
        return $query->where('space_code', $code);
    }
}
