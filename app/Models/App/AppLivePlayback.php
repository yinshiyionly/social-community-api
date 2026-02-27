<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppLivePlayback extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'app_live_playback';
    protected $primaryKey = 'playback_id';

    // 来源类型
    const SOURCE_AUTO_RECORD = 1;   // 自动录制
    const SOURCE_MANUAL_UPLOAD = 2; // 手动上传

    // 状态
    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;

    protected $fillable = [
        'room_id',
        'playback_title',
        'playback_url',
        'playback_cover',
        'playback_duration',
        'file_size',
        'source_type',
        'sort_order',
        'view_count',
        'status',
    ];

    protected $casts = [
        'playback_id' => 'integer',
        'room_id' => 'integer',
        'playback_duration' => 'integer',
        'file_size' => 'integer',
        'source_type' => 'integer',
        'sort_order' => 'integer',
        'view_count' => 'integer',
        'status' => 'integer',
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

    // ========== 查询作用域 ==========

    /**
     * 启用状态
     */
    public function scopeEnabled($query)
    {
        return $query->where('status', self::STATUS_ENABLED);
    }

    /**
     * 按直播间筛选
     */
    public function scopeByRoom($query, int $roomId)
    {
        return $query->where('room_id', $roomId);
    }
}
