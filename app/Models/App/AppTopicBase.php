<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 话题基础表
 */
class AppTopicBase extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'app_topic_base';

    protected $primaryKey = 'topic_id';

    protected $fillable = [
        'topic_name',
        'cover_url',
        'description',
        'view_count',
        'post_count',
        'sort_num',
        'is_recommend',
        'status',
    ];

    protected $casts = [
        'topic_id' => 'integer',
        'view_count' => 'integer',
        'post_count' => 'integer',
        'sort_num' => 'integer',
        'is_recommend' => 'integer',
        'status' => 'integer',
    ];

    // 推荐状态
    const RECOMMEND_NO = 0;
    const RECOMMEND_YES = 1;

    // 状态常量
    const STATUS_NORMAL = 1;
    const STATUS_DISABLED = 2;

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
        return $query->where('is_recommend', self::RECOMMEND_YES);
    }

    /**
     * 查询作用域 - 按排序号排序
     */
    public function scopeOrderBySort($query)
    {
        return $query->orderByDesc('sort_num');
    }
}
