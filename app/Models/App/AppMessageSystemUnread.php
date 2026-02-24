<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 系统消息按发送者未读数表
 */
class AppMessageSystemUnread extends Model
{
    use HasFactory;

    protected $table = 'app_message_system_unread';

    protected $primaryKey = 'id';

    protected $fillable = [
        'member_id',
        'sender_id',
        'unread_count',
    ];

    protected $casts = [
        'id' => 'integer',
        'member_id' => 'integer',
        'sender_id' => 'integer',
        'unread_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==================== 关联关系 ====================

    /**
     * 关联接收者
     */
    public function member()
    {
        return $this->belongsTo(AppMemberBase::class, 'member_id', 'member_id');
    }

    /**
     * 关联发送者（官方账号）
     */
    public function sender()
    {
        return $this->belongsTo(AppMemberBase::class, 'sender_id', 'member_id');
    }

    // ==================== 静态方法 ====================

    /**
     * 获取或创建未读数记录
     *
     * @param int $memberId
     * @param int $senderId
     * @return self
     */
    public static function getOrCreate(int $memberId, int $senderId): self
    {
        return self::firstOrCreate(
            ['member_id' => $memberId, 'sender_id' => $senderId],
            ['unread_count' => 0]
        );
    }

    /**
     * 增加未读数
     *
     * @param int $memberId
     * @param int $senderId
     * @param int $count
     * @return void
     */
    public static function incrementUnread(int $memberId, int $senderId, int $count = 1): void
    {
        self::getOrCreate($memberId, $senderId)->increment('unread_count', $count);
    }

    /**
     * 清空某发送者的未读数
     *
     * @param int $memberId
     * @param int $senderId
     * @return void
     */
    public static function clearUnread(int $memberId, int $senderId): void
    {
        self::where('member_id', $memberId)
            ->where('sender_id', $senderId)
            ->update(['unread_count' => 0]);
    }

    /**
     * 清空所有系统消息未读数
     *
     * @param int $memberId
     * @return void
     */
    public static function clearAll(int $memberId): void
    {
        self::where('member_id', $memberId)->update(['unread_count' => 0]);
    }

    /**
     * 获取会员所有发送者的未读数（含发送者信息）
     *
     * @param int $memberId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getByMemberWithSender(int $memberId)
    {
        return self::where('member_id', $memberId)
            ->where('unread_count', '>', 0)
            ->with('sender:member_id,nickname,avatar,is_official,official_label')
            ->get();
    }

    /**
     * 获取会员系统消息总未读数
     *
     * @param int $memberId
     * @return int
     */
    public static function getTotalUnread(int $memberId): int
    {
        return (int) self::where('member_id', $memberId)->sum('unread_count');
    }

    /**
     * 获取指定发送者的未读数
     *
     * @param int $memberId
     * @param int $senderId
     * @return int
     */
    public static function getUnreadCount(int $memberId, int $senderId): int
    {
        $record = self::where('member_id', $memberId)
            ->where('sender_id', $senderId)
            ->first();

        return $record ? $record->unread_count : 0;
    }
}
