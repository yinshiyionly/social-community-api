<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppLiveChatMessage extends Model
{
    use HasFactory;

    protected $table = 'app_live_chat_message';
    protected $primaryKey = 'message_id';

    // 只有 created_at，没有 updated_at
    const UPDATED_AT = null;

    // 消息类型
    const TYPE_TEXT = 1;       // 文本
    const TYPE_IMAGE = 2;      // 图片
    const TYPE_GIFT = 3;       // 礼物
    const TYPE_SYSTEM = 4;     // 系统消息
    const TYPE_RED_PACKET = 5; // 红包

    protected $fillable = [
        'room_id',
        'member_id',
        'member_name',
        'member_avatar',
        'message_type',
        'content',
        'ext_data',
        'is_top',
        'is_blocked',
    ];

    protected $casts = [
        'message_id' => 'integer',
        'room_id' => 'integer',
        'member_id' => 'integer',
        'message_type' => 'integer',
        'ext_data' => 'array',
        'is_top' => 'integer',
        'is_blocked' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * 关联直播间
     */
    public function room()
    {
        return $this->belongsTo(AppLiveRoom::class, 'room_id', 'room_id');
    }

    /**
     * 关联发送者
     */
    public function member()
    {
        return $this->belongsTo(AppMemberBase::class, 'member_id', 'member_id');
    }

    // ========== 查询作用域 ==========

    /**
     * 按直播间筛选
     */
    public function scopeByRoom($query, int $roomId)
    {
        return $query->where('room_id', $roomId);
    }

    /**
     * 未屏蔽的消息
     */
    public function scopeVisible($query)
    {
        return $query->where('is_blocked', 0);
    }

    /**
     * 按消息类型筛选
     */
    public function scopeByType($query, int $type)
    {
        return $query->where('message_type', $type);
    }
}
