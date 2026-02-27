<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppLiveViewerLog extends Model
{
    use HasFactory;

    protected $table = 'app_live_viewer_log';
    protected $primaryKey = 'id';

    // 只有 created_at，没有 updated_at
    const UPDATED_AT = null;

    protected $fillable = [
        'room_id',
        'member_id',
        'join_time',
        'leave_time',
        'watch_duration',
        'device_type',
        'ip_address',
    ];

    protected $casts = [
        'id' => 'integer',
        'room_id' => 'integer',
        'member_id' => 'integer',
        'join_time' => 'datetime',
        'leave_time' => 'datetime',
        'watch_duration' => 'integer',
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
     * 关联观看者
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
     * 按观看者筛选
     */
    public function scopeByMember($query, int $memberId)
    {
        return $query->where('member_id', $memberId);
    }
}
