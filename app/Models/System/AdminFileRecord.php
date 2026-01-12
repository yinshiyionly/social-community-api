<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Admin文件上传记录表
 */
class AdminFileRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'admin_file_records';

    protected $primaryKey = 'file_id';

    protected $fillable = [
        'file_name',
        'file_path',
        'file_driver',
        'file_hash',
        'file_size',
        'mime_type',
        'extension',
        'width',
        'height',
        'duration',
        'extra',
    ];

    protected $casts = [
        'file_id' => 'integer',
        'file_size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'duration' => 'integer',
        'extra' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // 存储驱动类型
    const DRIVER_TOS = 'tos';
    const DRIVER_OSS = 'oss';
    const DRIVER_S3 = 's3';

    /**
     * 根据文件哈希查找记录（用于去重）
     */
    public function scopeByHash($query, string $hash)
    {
        return $query->where('file_hash', $hash);
    }

    /**
     * 根据 MIME 类型筛选
     */
    public function scopeByMimeType($query, string $mimeType)
    {
        return $query->where('mime_type', $mimeType);
    }

    /**
     * 筛选图片文件
     */
    public function scopeImages($query)
    {
        return $query->where('mime_type', 'like', 'image/%');
    }

    /**
     * 筛选视频文件
     */
    public function scopeVideos($query)
    {
        return $query->where('mime_type', 'like', 'video/%');
    }

    /**
     * 筛选音频文件
     */
    public function scopeAudios($query)
    {
        return $query->where('mime_type', 'like', 'audio/%');
    }

    /**
     * 判断是否为图片
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * 判断是否为视频
     */
    public function isVideo(): bool
    {
        return str_starts_with($this->mime_type, 'video/');
    }

    /**
     * 判断是否为音频
     */
    public function isAudio(): bool
    {
        return str_starts_with($this->mime_type, 'audio/');
    }

    /**
     * 获取格式化的文件大小
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $index = 0;

        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }

        return round($bytes, 2) . ' ' . $units[$index];
    }
}
