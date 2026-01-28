<?php

namespace App\Models\App;

use App\Models\Traits\HasTosUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 广告内容表
 */
class AppAdItem extends Model
{
    use HasFactory, SoftDeletes, HasTosUrl;

    protected $table = 'app_ad_item';

    protected $primaryKey = 'ad_id';

    protected $fillable = [
        'space_id',
        'ad_title',
        'ad_type',
        'content_url',
        'target_type',
        'target_url',
        'sort_num',
        'status',
        'start_time',
        'end_time',
        'ext_json',
    ];

    protected $casts = [
        'ad_id' => 'integer',
        'space_id' => 'integer',
        'sort_num' => 'integer',
        'status' => 'integer',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'ext_json' => 'array',
    ];

    // 广告素材类型
    const AD_TYPE_IMAGE = 'image';
    const AD_TYPE_VIDEO = 'video';
    const AD_TYPE_TEXT = 'text';
    const AD_TYPE_HTML = 'html';

    // 跳转类型
    const TARGET_TYPE_EXTERNAL = 'external';
    const TARGET_TYPE_INTERNAL = 'internal';
    const TARGET_TYPE_NONE = 'none';

    // 状态常量
    const STATUS_ONLINE = 1;
    const STATUS_OFFLINE = 2;

    /**
     * 关联广告位
     */
    public function adSpace()
    {
        return $this->belongsTo(AppAdSpace::class, 'space_id', 'space_id');
    }

    /**
     * 查询作用域 - 上线状态
     */
    public function scopeOnline($query)
    {
        return $query->where('status', self::STATUS_ONLINE);
    }

    /**
     * 查询作用域 - 按广告位筛选
     */
    public function scopeBySpace($query, int $spaceId)
    {
        return $query->where('space_id', $spaceId);
    }

    /**
     * 查询作用域 - 有效期内
     */
    public function scopeInEffect($query)
    {
        $now = now();
        return $query->where(function ($q) use ($now) {
            $q->whereNull('start_time')->orWhere('start_time', '<=', $now);
        })->where(function ($q) use ($now) {
            $q->whereNull('end_time')->orWhere('end_time', '>=', $now);
        });
    }

    /**
     * 查询作用域 - 按优先级排序
     */
    public function scopeOrderByPriority($query)
    {
        return $query->orderByDesc('sort_num');
    }

    /**
     * 判断广告是否在有效期内
     */
    public function isInEffect(): bool
    {
        $now = now();
        $startValid = is_null($this->start_time) || $this->start_time <= $now;
        $endValid = is_null($this->end_time) || $this->end_time >= $now;
        return $startValid && $endValid;
    }

    /**
     * 拼接 TOS URL 绝对路径
     *
     * @param $value
     * @return string|null
     */
    public function getContentUrlAttribute($value): ?string
    {
        return $this->getTosUrl($value);
    }

    /**
     * 提取 TOS URL 相对路径
     *
     * @param $value
     * @return void
     */
    public function setContentUrlAttribute($value): void
    {
        $this->attributes['content_url'] = $this->extractTosPath($value);
    }
}
