<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppVideoBaijiayun extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'app_video_baijiayun';
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

    // 来源
    const SOURCE_BAIJIAYUN = 'baijiayun';

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
        'file_md5',
        'publish_status',
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

    /**
     * 获取转码状态文本
     */
    public function getStatusTextAttribute(): string
    {
        $map = self::getStatusTextMap();
        return isset($map[$this->status]) ? $map[$this->status] : '未知状态';
    }

    /**
     * 获取发布状态文本
     */
    public function getPublishStatusTextAttribute(): string
    {
        $map = self::getPublishStatusTextMap();
        return isset($map[$this->publish_status]) ? $map[$this->publish_status] : '未知状态';
    }

    /**
     * 获取格式化总大小
     */
    public function getFormattedTotalSizeAttribute(): string
    {
        if (!is_numeric($this->total_size)) {
            return (string) $this->total_size;
        }

        return self::formatBytes((float) $this->total_size);
    }

    /**
     * 获取格式化时长（HH:MM:SS）
     */
    public function getFormattedLengthAttribute(): string
    {
        $seconds = (int) $this->length;
        if ($seconds < 0) {
            $seconds = 0;
        }

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $remainSeconds = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $remainSeconds);
    }

    /**
     * 转码状态映射
     */
    public static function getStatusTextMap(): array
    {
        return [
            self::STATUS_UPLOADING => '上传中',
            self::STATUS_TRANSCODING => '转码中',
            self::STATUS_TRANSCODE_FAILED => '转码失败',
            self::STATUS_TRANSCODE_TIMEOUT => '转码超时',
            self::STATUS_UPLOAD_TIMEOUT => '上传超时',
            self::STATUS_TRANSCODE_SUCCESS => '转码成功',
        ];
    }

    /**
     * 发布状态映射
     */
    public static function getPublishStatusTextMap(): array
    {
        return [
            self::PUBLISH_STATUS_UNPUBLISHED => '未发布',
            self::PUBLISH_STATUS_PUBLISHED => '已发布',
        ];
    }

    /**
     * 转码状态选项
     */
    public static function getStatusOptions(): array
    {
        $options = [];
        foreach (self::getStatusTextMap() as $value => $label) {
            $options[] = [
                'label' => $label,
                'value' => $value,
            ];
        }

        return $options;
    }

    /**
     * 发布状态选项
     */
    public static function getPublishStatusOptions(): array
    {
        $options = [];
        foreach (self::getPublishStatusTextMap() as $value => $label) {
            $options[] = [
                'label' => $label,
                'value' => $value,
            ];
        }

        return $options;
    }

    /**
     * 格式化字节大小
     */
    protected static function formatBytes(float $bytes): string
    {
        $bytes = max($bytes, 0);
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $index = 0;

        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }

        $value = round($bytes, 2);
        if (floor($value) == $value) {
            $value = (int) $value;
        }

        return $value . ' ' . $units[$index];
    }
}
