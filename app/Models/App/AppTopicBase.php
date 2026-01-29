<?php

namespace App\Models\App;

use App\Models\Traits\HasTosUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 话题基础表
 *
 * @property int $topic_id
 * @property string $topic_name
 * @property string $cover_url
 * @property string $description
 * @property string|null $detail_html
 * @property int $creator_id
 * @property int $sort_num
 * @property int $is_recommend
 * @property int $is_official
 * @property int $status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class AppTopicBase extends Model
{
    use HasFactory, SoftDeletes, HasTosUrl;

    protected $table = 'app_topic_base';

    protected $primaryKey = 'topic_id';

    protected $fillable = [
        'topic_name',
        'cover_url',
        'description',
        'detail_html',
        'creator_id',
        'sort_num',
        'is_recommend',
        'is_official',
        'status',
    ];

    protected $casts = [
        'topic_id' => 'integer',
        'creator_id' => 'integer',
        'sort_num' => 'integer',
        'is_recommend' => 'integer',
        'is_official' => 'integer',
        'status' => 'integer',
    ];

    // 状态
    const STATUS_NORMAL = 1;    // 正常
    const STATUS_DISABLED = 2;  // 禁用

    // 是否推荐
    const IS_RECOMMEND_NO = 0;
    const IS_RECOMMEND_YES = 1;

    // 是否官方
    const IS_OFFICIAL_NO = 0;
    const IS_OFFICIAL_YES = 1;

    /**
     * 查询作用域 - 正常状态
     */
    public function scopeNormal($query)
    {
        return $query->where('status', self::STATUS_NORMAL);
    }

    /**
     * 查询作用域 - 推荐话题
     */
    public function scopeRecommend($query)
    {
        return $query->where('is_recommend', self::IS_RECOMMEND_YES);
    }

    /**
     * 查询作用域 - 官方话题
     */
    public function scopeOfficial($query)
    {
        return $query->where('is_official', self::IS_OFFICIAL_YES);
    }

    /**
     * 查询作用域 - 按排序号排序
     */
    public function scopeOrderBySort($query)
    {
        return $query->orderByDesc('sort_num')->orderByDesc('topic_id');
    }

    /**
     * 关联统计数据
     */
    public function stat()
    {
        return $this->hasOne(AppTopicStat::class, 'topic_id', 'topic_id');
    }

    /**
     * 关联帖子（多对多）
     */
    public function posts()
    {
        return $this->belongsToMany(
            AppPostBase::class,
            'app_topic_post_relation',
            'topic_id',
            'post_id',
            'topic_id',
            'post_id'
        )->withPivot('is_featured', 'created_at');
    }

    /**
     * 关联创建者
     */
    public function creator()
    {
        return $this->belongsTo(AppMemberBase::class, 'creator_id', 'member_id');
    }

    /**
     * 设置 cover_url - 将绝对路径转为相对路径存储
     */
    public function setCoverUrlAttribute($value): void
    {
        $this->attributes['cover_url'] = $this->extractTosPath($value);
    }

    /**
     * 获取 cover_url - 将相对路径转为绝对路径
     */
    public function getCoverUrlAttribute($value): string
    {
        return $this->getTosUrl($value);
    }
}
