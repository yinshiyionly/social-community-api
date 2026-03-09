<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 百家云直播回放模型。
 *
 * 数据语义：
 * 1. playback_id 是第三方回放唯一键，用于幂等同步；
 * 2. room_id 关联本地直播间，third_party_room_id 保留百家云教室号；
 * 3. create_time 为百家云回放生成时间，不等于本地 created_at。
 */
class AppLivePlayback extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'app_live_playback';
    protected $primaryKey = 'id';

    // 转码状态
    const STATUS_GENERATING = 10;        // 生成中
    const STATUS_TRANSCODING = 20;       // 转码中
    const STATUS_TRANSCODE_FAILED = 30;  // 转码失败
    const STATUS_TRANSCODE_SUCCESS = 100;// 转码成功

    // 屏蔽状态
    const PUBLISH_STATUS_UNSHIELDED = 1; // 未屏蔽
    const PUBLISH_STATUS_SHIELDED = 2;   // 已屏蔽

    protected $fillable = [
        'playback_id',
        'room_id',
        'third_party_room_id',
        'session_id',
        'video_id',
        'name',
        'status',
        'create_time',
        'length',
        'total_transcode_size',
        'play_times',
        'play_url',
        'preface_url',
        'publish_status',
        'version',
    ];

    protected $casts = [
        'id' => 'integer',
        'playback_id' => 'integer',
        'room_id' => 'integer',
        'session_id' => 'integer',
        'video_id' => 'integer',
        'status' => 'integer',
        'create_time' => 'datetime',
        'length' => 'integer',
        'total_transcode_size' => 'integer',
        'play_times' => 'integer',
        'publish_status' => 'integer',
        'version' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 关联本地直播间。
     */
    public function room()
    {
        return $this->belongsTo(AppLiveRoom::class, 'room_id', 'room_id');
    }

    /**
     * 按本地直播间筛选回放。
     */
    public function scopeByRoom($query, int $roomId)
    {
        return $query->where('room_id', $roomId);
    }

    /**
     * 按第三方回放ID筛选。
     */
    public function scopeByPlaybackId($query, int $playbackId)
    {
        return $query->where('playback_id', $playbackId);
    }

    /**
     * 转码成功的回放。
     */
    public function scopeTranscoded($query)
    {
        return $query->where('status', self::STATUS_TRANSCODE_SUCCESS);
    }

    /**
     * 未屏蔽回放（可展示）。
     */
    public function scopeUnshielded($query)
    {
        return $query->where('publish_status', self::PUBLISH_STATUS_UNSHIELDED);
    }
}
