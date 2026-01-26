<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 动态基础表
 */
class AppPostBase extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'app_post_base';

    protected $primaryKey = 'post_id';

    protected $fillable = [
        'member_id',
        'post_type',
        'title',
        'content',
        'media_data',
        'location_name',
        'location_geo',
        'view_count',
        'like_count',
        'comment_count',
        'share_count',
        'collection_count',
        'is_top',
        'sort_score',
        'visible',
        'status',
        'audit_msg',
    ];

    protected $casts = [
        'post_id' => 'integer',
        'member_id' => 'integer',
        'post_type' => 'integer',
        'media_data' => 'array',
        'location_geo' => 'array',
        'view_count' => 'integer',
        'like_count' => 'integer',
        'comment_count' => 'integer',
        'share_count' => 'integer',
        'collection_count' => 'integer',
        'is_top' => 'integer',
        'sort_score' => 'float',
        'visible' => 'integer',
        'status' => 'integer',
    ];

    // 动态类型
    const POST_TYPE_NORMAL = 1;     // 普通动态

    // 可见性
    const VISIBLE_PUBLIC = 1;       // 公开
    const VISIBLE_PRIVATE = 0;      // 私密

    // 状态
    const STATUS_PENDING = 0;       // 待审核
    const STATUS_APPROVED = 1;      // 已通过
    const STATUS_REJECTED = 2;      // 已拒绝

    // 置顶
    const IS_TOP_NO = 0;
    const IS_TOP_YES = 1;

    /**
     * 查询作用域 - 已审核通过
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * 查询作用域 - 公开可见
     */
    public function scopeVisible($query)
    {
        return $query->where('visible', self::VISIBLE_PUBLIC);
    }

    /**
     * 查询作用域 - 按用户筛选
     */
    public function scopeByMember($query, int $memberId)
    {
        return $query->where('member_id', $memberId);
    }

    /**
     * 查询作用域 - 按类型筛选
     */
    public function scopeByType($query, int $postType)
    {
        return $query->where('post_type', $postType);
    }

    /**
     * 查询作用域 - 置顶优先排序
     */
    public function scopeOrderByTop($query)
    {
        return $query->orderByDesc('is_top')->orderByDesc('created_at');
    }

    /**
     * 增加浏览量
     */
    public function incrementViewCount(int $count = 1): bool
    {
        return $this->increment('view_count', $count);
    }

    /**
     * 增加点赞数
     */
    public function incrementLikeCount(int $count = 1): bool
    {
        return $this->increment('like_count', $count);
    }

    /**
     * 减少点赞数
     */
    public function decrementLikeCount(int $count = 1): bool
    {
        return $this->decrement('like_count', $count);
    }

    /**
     * 增加评论数
     */
    public function incrementCommentCount(int $count = 1): bool
    {
        return $this->increment('comment_count', $count);
    }

    /**
     * 减少评论数
     */
    public function decrementCommentCount(int $count = 1): bool
    {
        return $this->decrement('comment_count', $count);
    }

    /**
     * 增加分享数
     */
    public function incrementShareCount(int $count = 1): bool
    {
        return $this->increment('share_count', $count);
    }

    /**
     * 增加收藏数
     */
    public function incrementCollectionCount(int $count = 1): bool
    {
        return $this->increment('collection_count', $count);
    }

    /**
     * 减少收藏数
     */
    public function decrementCollectionCount(int $count = 1): bool
    {
        return $this->decrement('collection_count', $count);
    }

    /**
     * 关联作者（会员）
     */
    public function member()
    {
        return $this->belongsTo(AppMemberBase::class, 'member_id', 'member_id');
    }
}
