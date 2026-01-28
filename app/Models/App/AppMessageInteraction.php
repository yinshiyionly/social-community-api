<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 互动消息表
 */
class AppMessageInteraction extends Model
{
    use HasFactory;

    protected $table = 'app_message_interaction';

    protected $primaryKey = 'message_id';

    protected $fillable = [
        'receiver_id',
        'sender_id',
        'message_type',
        'target_id',
        'target_type',
        'content_summary',
        'cover_url',
        'is_read',
    ];

    protected $casts = [
        'message_id' => 'integer',
        'receiver_id' => 'integer',
        'sender_id' => 'integer',
        'message_type' => 'integer',
        'target_id' => 'integer',
        'target_type' => 'integer',
        'is_read' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // 消息类型常量
    const TYPE_LIKE = 1;      // 点赞
    const TYPE_COLLECT = 2;   // 收藏
    const TYPE_COMMENT = 3;   // 评论
    const TYPE_FOLLOW = 4;    // 关注

    // 目标类型常量
    const TARGET_POST = 1;     // 帖子
    const TARGET_COMMENT = 2;  // 评论

    // 已读状态常量
    const READ_NO = 0;   // 未读
    const READ_YES = 1;  // 已读

    // ==================== 关联关系 ====================

    /**
     * 关联接收者
     */
    public function receiver()
    {
        return $this->belongsTo(AppMemberBase::class, 'receiver_id', 'member_id');
    }

    /**
     * 关联发送者
     */
    public function sender()
    {
        return $this->belongsTo(AppMemberBase::class, 'sender_id', 'member_id');
    }

    /**
     * 关联帖子（当 target_type = 1 时）
     */
    public function post()
    {
        return $this->belongsTo(AppPostBase::class, 'target_id', 'post_id');
    }

    /**
     * 关联评论（当 target_type = 2 时）
     */
    public function comment()
    {
        return $this->belongsTo(AppPostComment::class, 'target_id', 'comment_id');
    }

    // ==================== 查询作用域 ====================

    /**
     * 按接收者筛选
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $receiverId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByReceiver($query, int $receiverId)
    {
        return $query->where('receiver_id', $receiverId);
    }

    /**
     * 按消息类型筛选
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType($query, int $type)
    {
        return $query->where('message_type', $type);
    }

    /**
     * 按多个消息类型筛选
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $types
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByTypes($query, array $types)
    {
        return $query->whereIn('message_type', $types);
    }

    /**
     * 未读消息
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', self::READ_NO);
    }

    /**
     * 已读消息
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', self::READ_YES);
    }

    /**
     * 点赞和收藏消息
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLikeAndCollect($query)
    {
        return $query->whereIn('message_type', [self::TYPE_LIKE, self::TYPE_COLLECT]);
    }

    // ==================== 静态方法 ====================

    /**
     * 获取未读消息数量
     *
     * @param int $receiverId
     * @param int|null $type
     * @return int
     */
    public static function getUnreadCount(int $receiverId, ?int $type = null): int
    {
        $query = self::byReceiver($receiverId)->unread();

        if ($type !== null) {
            $query->byType($type);
        }

        return $query->count();
    }

    /**
     * 标记消息为已读
     *
     * @param int $receiverId
     * @param int|null $type
     * @return int
     */
    public static function markAsRead(int $receiverId, ?int $type = null): int
    {
        $query = self::byReceiver($receiverId)->unread();

        if ($type !== null) {
            $query->byType($type);
        }

        return $query->update(['is_read' => self::READ_YES]);
    }
}
