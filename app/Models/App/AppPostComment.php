<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 帖子评论表
 */
class AppPostComment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'app_post_comment';

    protected $primaryKey = 'comment_id';

    protected $fillable = [
        'post_id',
        'member_id',
        'parent_id',
        'reply_to_member_id',
        'content',
        'like_count',
        'reply_count',
        'status',
        'ip_address',
        'ip_region',
    ];

    protected $casts = [
        'comment_id' => 'integer',
        'post_id' => 'integer',
        'member_id' => 'integer',
        'parent_id' => 'integer',
        'reply_to_member_id' => 'integer',
        'like_count' => 'integer',
        'reply_count' => 'integer',
        'status' => 'integer',
    ];

    // 状态常量
    const STATUS_PENDING = 0;   // 待审核
    const STATUS_NORMAL = 1;    // 正常
    const STATUS_DELETED = 2;   // 已删除

    /**
     * 关联帖子
     */
    public function post()
    {
        return $this->belongsTo(AppPostBase::class, 'post_id', 'post_id');
    }

    /**
     * 关联评论者
     */
    public function member()
    {
        return $this->belongsTo(AppMemberBase::class, 'member_id', 'member_id');
    }

    /**
     * 关联被回复的用户
     */
    public function replyToMember()
    {
        return $this->belongsTo(AppMemberBase::class, 'reply_to_member_id', 'member_id');
    }

    /**
     * 关联父评论
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id', 'comment_id');
    }

    /**
     * 关联子评论（回复）
     */
    public function replies()
    {
        return $this->hasMany(self::class, 'parent_id', 'comment_id');
    }

    /**
     * 查询作用域 - 正常状态
     */
    public function scopeNormal($query)
    {
        return $query->where('status', self::STATUS_NORMAL);
    }

    /**
     * 查询作用域 - 一级评论
     */
    public function scopeTopLevel($query)
    {
        return $query->where('parent_id', 0);
    }

    /**
     * 查询作用域 - 按帖子筛选
     */
    public function scopeByPost($query, int $postId)
    {
        return $query->where('post_id', $postId);
    }

    /**
     * 查询作用域 - 按父评论筛选
     */
    public function scopeByParent($query, int $parentId)
    {
        return $query->where('parent_id', $parentId);
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
     * 增加回复数
     */
    public function incrementReplyCount(int $count = 1): bool
    {
        return $this->increment('reply_count', $count);
    }

    /**
     * 减少回复数
     */
    public function decrementReplyCount(int $count = 1): bool
    {
        return $this->decrement('reply_count', $count);
    }

    /**
     * 是否为一级评论
     */
    public function isTopLevel(): bool
    {
        return $this->parent_id === 0;
    }
}
