<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 消息未读数统计表
 */
class AppMessageUnreadCount extends Model
{
    use HasFactory;

    protected $table = 'app_message_unread_count';

    protected $primaryKey = 'member_id';

    public $incrementing = false;

    protected $fillable = [
        'member_id',
        'like_count',
        'collect_count',
        'comment_count',
        'follow_count',
        'system_count',
    ];

    protected $casts = [
        'member_id' => 'integer',
        'like_count' => 'integer',
        'collect_count' => 'integer',
        'comment_count' => 'integer',
        'follow_count' => 'integer',
        'system_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==================== 关联关系 ====================

    /**
     * 关联会员
     */
    public function member()
    {
        return $this->belongsTo(AppMemberBase::class, 'member_id', 'member_id');
    }

    // ==================== 静态方法 ====================

    /**
     * 获取或创建未读数记录
     *
     * @param int $memberId
     * @return self
     */
    public static function getOrCreate(int $memberId): self
    {
        return self::firstOrCreate(
            ['member_id' => $memberId],
            [
                'like_count' => 0,
                'collect_count' => 0,
                'comment_count' => 0,
                'follow_count' => 0,
                'system_count' => 0,
            ]
        );
    }

    /**
     * 增加点赞未读数
     *
     * @param int $memberId
     * @param int $count
     * @return void
     */
    public static function incrementLike(int $memberId, int $count = 1): void
    {
        self::getOrCreate($memberId)->increment('like_count', $count);
    }

    /**
     * 增加收藏未读数
     *
     * @param int $memberId
     * @param int $count
     * @return void
     */
    public static function incrementCollect(int $memberId, int $count = 1): void
    {
        self::getOrCreate($memberId)->increment('collect_count', $count);
    }

    /**
     * 增加评论未读数
     *
     * @param int $memberId
     * @param int $count
     * @return void
     */
    public static function incrementComment(int $memberId, int $count = 1): void
    {
        self::getOrCreate($memberId)->increment('comment_count', $count);
    }

    /**
     * 增加关注未读数
     *
     * @param int $memberId
     * @param int $count
     * @return void
     */
    public static function incrementFollow(int $memberId, int $count = 1): void
    {
        self::getOrCreate($memberId)->increment('follow_count', $count);
    }

    /**
     * 增加系统消息未读数
     *
     * @param int $memberId
     * @param int $count
     * @return void
     */
    public static function incrementSystem(int $memberId, int $count = 1): void
    {
        self::getOrCreate($memberId)->increment('system_count', $count);
    }

    /**
     * 清空点赞未读数
     *
     * @param int $memberId
     * @return void
     */
    public static function clearLike(int $memberId): void
    {
        self::where('member_id', $memberId)->update(['like_count' => 0]);
    }

    /**
     * 清空收藏未读数
     *
     * @param int $memberId
     * @return void
     */
    public static function clearCollect(int $memberId): void
    {
        self::where('member_id', $memberId)->update(['collect_count' => 0]);
    }

    /**
     * 清空评论未读数
     *
     * @param int $memberId
     * @return void
     */
    public static function clearComment(int $memberId): void
    {
        self::where('member_id', $memberId)->update(['comment_count' => 0]);
    }

    /**
     * 清空关注未读数
     *
     * @param int $memberId
     * @return void
     */
    public static function clearFollow(int $memberId): void
    {
        self::where('member_id', $memberId)->update(['follow_count' => 0]);
    }

    /**
     * 清空系统消息未读数
     *
     * @param int $memberId
     * @return void
     */
    public static function clearSystem(int $memberId): void
    {
        self::where('member_id', $memberId)->update(['system_count' => 0]);
    }

    /**
     * 获取总未读数
     *
     * @return int
     */
    public function getTotalCount(): int
    {
        return $this->like_count
            + $this->collect_count
            + $this->comment_count
            + $this->follow_count
            + $this->system_count;
    }

    /**
     * 获取赞和收藏合计未读数
     *
     * @return int
     */
    public function getLikeAndCollectCount(): int
    {
        return $this->like_count + $this->collect_count;
    }
}
