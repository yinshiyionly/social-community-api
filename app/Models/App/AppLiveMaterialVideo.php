<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppLiveMaterialVideo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'app_live_material_video';
    protected $primaryKey = 'id';

    // 转码状态
    const STATUS_UPLOADING = 10;         // 上传中
    const STATUS_TRANSCODING = 20;       // 转码中
    const STATUS_TRANSCODE_FAILED = 30;  // 转码失败
    const STATUS_TRANSCODE_TIMEOUT = 31; // 转码超时
    const STATUS_UPLOAD_TIMEOUT = 32;    // 上传超时
    const STATUS_TRANSCODE_SUCCESS = 100;// 转码成功

    // 发布状态
    const PUBLISH_STATUS_UNPUBLISHED = 0; // 未发布
    const PUBLISH_STATUS_PUBLISHED = 1;   // 已发布

    // 存储位置
    const STORAGE_BJY = 'bjy'; // 百家云
    const STORAGE_TOS = 'tos'; // 火山云

    protected $fillable = [
        'video_id',
        'name',
        'status',
        'total_size',
        'preface_url',
        'play_url',
        'length',
        'width',
        'height',
        'publish_status',
        'storage',
    ];

    protected $casts = [
        'id' => 'integer',
        'video_id' => 'integer',
        'status' => 'integer',
        'length' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'publish_status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ========== 查询作用域 ==========

    /**
     * 转码成功的视频
     */
    public function scopeTranscoded($query)
    {
        return $query->where('status', self::STATUS_TRANSCODE_SUCCESS);
    }

    /**
     * 已发布的视频
     */
    public function scopePublished($query)
    {
        return $query->where('publish_status', self::PUBLISH_STATUS_PUBLISHED);
    }

    /**
     * 按存储位置筛选
     */
    public function scopeByStorage($query, string $storage)
    {
        return $query->where('storage', $storage);
    }

    // ========== 状态判断 ==========

    /**
     * 是否转码成功
     */
    public function isTranscoded(): bool
    {
        return $this->status === self::STATUS_TRANSCODE_SUCCESS;
    }

    /**
     * 是否已发布
     */
    public function isPublished(): bool
    {
        return $this->publish_status === self::PUBLISH_STATUS_PUBLISHED;
    }
}
