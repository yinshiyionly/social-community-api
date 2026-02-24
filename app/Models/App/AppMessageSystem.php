<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 系统消息表
 */
class AppMessageSystem extends Model
{
    use HasFactory;

    protected $table = 'app_message_system';

    protected $primaryKey = 'message_id';

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'title',
        'content',
        'cover_url',
        'link_type',
        'link_url',
        'is_read',
    ];

    protected $casts = [
        'message_id' => 'integer',
        'sender_id' => 'integer',
        'receiver_id' => 'integer',
        'link_type' => 'integer',
        'is_read' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // 跳转类型常量
    const LINK_POST = 1;       // 帖子详情
    const LINK_ACTIVITY = 2;   // 活动页
    const LINK_EXTERNAL = 3;   // 外链
    const LINK_NONE = 4;       // 无跳转

    // 已读状态常量
    const READ_NO = 0;   // 未读
    const READ_YES = 1;  // 已读

    // ==================== 关联关系 ====================

    /**
     * 关联发送者（官方账号）
     */
    public function sender()
    {
        return $this->belongsTo(AppMemberBase::class, 'sender_id', 'member_id');
    }

    /**
     * 关联接收者
     */
    public function receiver()
    {
        return $this->belongsTo(AppMemberBase::class, 'receiver_id', 'member_id');
    }

    // ==================== 查询作用域 ====================

    /**
     * 按接收者筛选（包含全员消息）
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $receiverId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForReceiver($query, int $receiverId)
    {
        return $query->where(function ($q) use ($receiverId) {
            $q->where('receiver_id', $receiverId)
              ->orWhereNull('receiver_id');
        });
    }

    /**
     * 仅定向消息
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
     * 仅全员广播消息
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBroadcast($query)
    {
        return $query->whereNull('receiver_id');
    }

    /**
     * 按发送者筛选
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $senderId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBySender($query, int $senderId)
    {
        return $query->where('sender_id', $senderId);
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

    // ==================== 静态方法 ====================

    /**
     * 获取未读消息数量
     *
     * @param int $receiverId
     * @return int
     */
    public static function getUnreadCount(int $receiverId): int
    {
        return self::forReceiver($receiverId)->unread()->count();
    }

    /**
     * 标记消息为已读
     *
     * @param int $receiverId
     * @return int
     */
    public static function markAsRead(int $receiverId): int
    {
        return self::byReceiver($receiverId)->unread()->update(['is_read' => self::READ_YES]);
    }

    /**
     * 判断是否为广播消息
     *
     * @return bool
     */
    public function isBroadcast(): bool
    {
        return $this->receiver_id === null;
    }
}
