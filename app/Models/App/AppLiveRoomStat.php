<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppLiveRoomStat extends Model
{
    use HasFactory;

    protected $table = 'app_live_room_stat';
    protected $primaryKey = 'room_id';

    // IDENTITY 主键不自增，使用业务主键
    public $incrementing = false;

    protected $fillable = [
        'room_id',
        'total_viewer_count',
        'max_online_count',
        'current_online_count',
        'like_count',
        'message_count',
        'gift_count',
        'gift_amount',
        'share_count',
        'avg_watch_duration',
    ];

    protected $casts = [
        'room_id' => 'integer',
        'total_viewer_count' => 'integer',
        'max_online_count' => 'integer',
        'current_online_count' => 'integer',
        'like_count' => 'integer',
        'message_count' => 'integer',
        'gift_count' => 'integer',
        'gift_amount' => 'decimal:2',
        'share_count' => 'integer',
        'avg_watch_duration' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 关联直播间
     */
    public function room()
    {
        return $this->belongsTo(AppLiveRoom::class, 'room_id', 'room_id');
    }

    /**
     * 更新最高在线人数
     *
     * @param int $currentOnline 当前在线人数
     */
    public function updateMaxOnline(int $currentOnline): void
    {
        $this->current_online_count = $currentOnline;
        if ($currentOnline > $this->max_online_count) {
            $this->max_online_count = $currentOnline;
        }
        $this->save();
    }

    /**
     * 增加消息计数
     */
    public function incrementMessageCount(): void
    {
        $this->increment('message_count');
    }

    /**
     * 增加点赞计数
     */
    public function incrementLikeCount(): void
    {
        $this->increment('like_count');
    }
}
