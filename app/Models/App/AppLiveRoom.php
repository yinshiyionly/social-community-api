<?php

namespace App\Models\App;

use App\Models\Traits\HasOperator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppLiveRoom extends Model
{
    use HasFactory, SoftDeletes, HasOperator;

    protected $table = 'app_live_room';
    protected $primaryKey = 'room_id';

    // 直播类型
    const LIVE_TYPE_REAL = 1;      // 真实直播
    const LIVE_TYPE_PSEUDO = 2;    // 伪直播

    // 直播平台
    const PLATFORM_CUSTOM = 'custom';
    const PLATFORM_BAIJIAYUN = 'baijiayun';
    const PLATFORM_ALIYUN = 'aliyun';
    const PLATFORM_TENCENT = 'tencent';
    const PLATFORM_AGORA = 'agora';

    // 直播状态
    const LIVE_STATUS_NOT_STARTED = 0;  // 未开始
    const LIVE_STATUS_LIVING = 1;       // 直播中
    const LIVE_STATUS_ENDED = 2;        // 已结束
    const LIVE_STATUS_CANCELLED = 3;    // 已取消

    // 启用状态
    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;

    protected $fillable = [
        'room_title',
        'room_cover',
        'room_intro',
        'live_type',
        'live_platform',
        'third_party_room_id',
        'push_url',
        'pull_url',
        'video_url',
        'anchor_id',
        'anchor_name',
        'anchor_avatar',
        'scheduled_start_time',
        'scheduled_end_time',
        'actual_start_time',
        'actual_end_time',
        'live_duration',
        'live_status',
        'allow_chat',
        'allow_gift',
        'allow_like',
        'password',
        'ext_config',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'room_id' => 'integer',
        'live_type' => 'integer',
        'anchor_id' => 'integer',
        'scheduled_start_time' => 'datetime',
        'scheduled_end_time' => 'datetime',
        'actual_start_time' => 'datetime',
        'actual_end_time' => 'datetime',
        'live_duration' => 'integer',
        'live_status' => 'integer',
        'allow_chat' => 'integer',
        'allow_gift' => 'integer',
        'allow_like' => 'integer',
        'ext_config' => 'array',
        'status' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'deleted_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ========== 关联关系 ==========

    /**
     * 直播间统计数据
     */
    public function stat()
    {
        return $this->hasOne(AppLiveRoomStat::class, 'room_id', 'room_id');
    }

    /**
     * 直播回放列表
     */
    public function playbacks()
    {
        return $this->hasMany(AppLivePlayback::class, 'room_id', 'room_id');
    }

    /**
     * 聊天消息
     */
    public function chatMessages()
    {
        return $this->hasMany(AppLiveChatMessage::class, 'room_id', 'room_id');
    }

    /**
     * 观看记录
     */
    public function viewerLogs()
    {
        return $this->hasMany(AppLiveViewerLog::class, 'room_id', 'room_id');
    }

    /**
     * 关联的章节直播内容
     */
    public function chapterContent()
    {
        return $this->hasOne(AppChapterContentLive::class, 'room_id', 'room_id');
    }

    // ========== 查询作用域 ==========

    /**
     * 启用状态
     */
    public function scopeEnabled($query)
    {
        return $query->where('status', self::STATUS_ENABLED);
    }

    /**
     * 按直播状态筛选
     */
    public function scopeByLiveStatus($query, int $liveStatus)
    {
        return $query->where('live_status', $liveStatus);
    }

    /**
     * 按直播类型筛选
     */
    public function scopeByLiveType($query, int $liveType)
    {
        return $query->where('live_type', $liveType);
    }

    /**
     * 按平台筛选
     */
    public function scopeByPlatform($query, string $platform)
    {
        return $query->where('live_platform', $platform);
    }

    // ========== 状态判断 ==========

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
     * 是否已取消
     */
    public function isCancelled(): bool
    {
        return $this->live_status === self::LIVE_STATUS_CANCELLED;
    }

    /**
     * 是否真实直播
     */
    public function isRealLive(): bool
    {
        return $this->live_type === self::LIVE_TYPE_REAL;
    }

    /**
     * 是否伪直播
     */
    public function isPseudoLive(): bool
    {
        return $this->live_type === self::LIVE_TYPE_PSEUDO;
    }

    /**
     * 是否可观看（直播中或有回放）
     */
    public function isWatchable(): bool
    {
        if ($this->isLiving()) {
            return true;
        }

        if ($this->isEnded()) {
            $stat = $this->stat;
            return $stat ? true : false;
        }

        return false;
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

        return isset($map[$this->live_status]) ? $map[$this->live_status] : '未知';
    }

    // ========== 业务方法 ==========

    /**
     * 开始直播
     */
    public function startLive(): bool
    {
        $this->live_status = self::LIVE_STATUS_LIVING;
        $this->actual_start_time = now();
        return $this->save();
    }

    /**
     * 结束直播
     */
    public function endLive(): bool
    {
        $this->live_status = self::LIVE_STATUS_ENDED;
        $this->actual_end_time = now();
        return $this->save();
    }

    /**
     * 取消直播
     */
    public function cancelLive(): bool
    {
        $this->live_status = self::LIVE_STATUS_CANCELLED;
        return $this->save();
    }
}
