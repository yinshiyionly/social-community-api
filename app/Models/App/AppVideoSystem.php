<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppVideoSystem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'app_video_system';
    protected $primaryKey = 'video_id';

    const STATUS_ENABLED = 1;  // 启用
    const STATUS_DISABLED = 2; // 禁用

    // 来源
    const SOURCE_SYSTEM = 'system';

    protected $fillable = [
        'name',
        'status',
        'total_size',
        'preface_url',
        'play_url',
        'length',
        'width',
        'height',
    ];

    protected $casts = [
        'video_id' => 'integer',
        'status' => 'integer',
        'length' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 启用状态
     */
    public function scopeEnabled($query)
    {
        return $query->where('status', self::STATUS_ENABLED);
    }

    /**
     * 是否启用
     */
    public function isEnabled(): bool
    {
        return $this->status === self::STATUS_ENABLED;
    }

    /**
     * 获取状态文本
     */
    public function getStatusTextAttribute(): string
    {
        $map = self::getStatusTextMap();
        return isset($map[$this->status]) ? $map[$this->status] : '未知状态';
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
     * 状态映射
     */
    public static function getStatusTextMap(): array
    {
        return [
            self::STATUS_ENABLED => '启用',
            self::STATUS_DISABLED => '禁用',
        ];
    }

    /**
     * 状态选项
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
