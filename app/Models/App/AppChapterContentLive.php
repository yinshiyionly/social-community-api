<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppChapterContentLive extends Model
{
    use HasFactory;

    protected $table = 'app_chapter_content_live';
    protected $primaryKey = 'id';
    public $timestamps = false;

    // 直播平台
    const PLATFORM_CUSTOM = 'custom';
    const PLATFORM_ALIYUN = 'aliyun';
    const PLATFORM_TENCENT = 'tencent';
    const PLATFORM_AGORA = 'agora';

    // 直播状态
    const LIVE_STATUS_NOT_STARTED = 0;  // 未开始
    const LIVE_STATUS_LIVING = 1;       // 直播中
    const LIVE_STATUS_ENDED = 2;        // 已结束
    const LIVE_STATUS_CANCELLED = 3;    // 已取消

    protected $fillable = [
        'chapter_id',
        'live_platform',
        'live_room_id',
        'live_push_url',
        'live_pull_url',
        'live_cover',
        'live_start_time',
        'live_end_time',
        'live_duration',
        'live_status',
        'has_playback',
        'playback_url',
        'playback_duration',
        'allow_chat',
        'allow_gift',
        'online_count',
        'max_online_count',
        'attachments',
        'create_time',
        'update_time',
    ];

    protected $casts = [
        'id' => 'integer',
        'chapter_id' => 'integer',
        'live_start_time' => 'datetime',
        'live_end_time' => 'datetime',
        'live_duration' => 'integer',
        'live_status' => 'integer',
        'has_playback' => 'integer',
        'playback_duration' => 'integer',
        'allow_chat' => 'integer',
        'allow_gift' => 'integer',
        'online_count' => 'integer',
        'max_online_count' => 'integer',
        'attachments' => 'array',
        'create_time' => 'datetime',
        'update_time' => 'datetime',
    ];

    /**
     * 关联章节
     */
    public function chapter()
    {
        return $this->belongsTo(AppCourseChapter::class, 'chapter_id', 'chapter_id');
    }

    /**
     * 是否未开始
     */
    public function isNotStarted(): bool
    {
        return $this->live_status === self::LIVE_STATUS_NOT_STARTED;
    }

    /**
     * 是否直播中
     */
    public function isLiving(): bool
    {
        return $this->live_status === self::LIVE_STATUS_LIVING;
    }

    /**
     * 是否已结束
     */
    public function isEnded(): bool
    {
        return $this->live_status === self::LIVE_STATUS_ENDED;
    }

    /**
     * 是否可观看（直播中或有回放）
     */
    public function isWatchable(): bool
    {
        return $this->isLiving() || ($this->isEnded() && $this->has_playback);
    }

    /**
     * 获取直播状态文本
     */
    public function getLiveStatusTextAttribute(): string
    {
        $map = [
            self::LIVE_STATUS_NOT_STARTED => '未开始',
            self::LIVE_STATUS_LIVING => '直播中',
            self::LIVE_STATUS_ENDED => '已结束',
            self::LIVE_STATUS_CANCELLED => '已取消',
        ];

        return $map[$this->live_status] ?? '未知';
    }

    /**
     * 开始直播
     */
    public function startLive(): bool
    {
        $this->live_status = self::LIVE_STATUS_LIVING;
        $this->live_start_time = now();
        $this->update_time = now();
        return $this->save();
    }

    /**
     * 结束直播
     */
    public function endLive(): bool
    {
        $this->live_status = self::LIVE_STATUS_ENDED;
        $this->live_end_time = now();
        $this->update_time = now();
        return $this->save();
    }
}
