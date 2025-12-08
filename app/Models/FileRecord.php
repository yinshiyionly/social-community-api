<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use App\Models\System\SystemUser;

class FileRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'original_name',
        'storage_path',
        'storage_disk',
        'file_hash',
        'file_size',
        'mime_type',
        'extension',
        'user_id'
    ];

    protected $casts = [
        'file_size' => 'integer',
        'user_id' => 'integer',
    ];

    /**
     * 关联关系 - 上传者
     */
    public function user()
    {
        return $this->belongsTo(SystemUser::class, 'user_id', 'user_id');
    }

    /**
     * 查询作用域 - 按用户筛选
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * 查询作用域 - 按文件哈希筛选
     */
    public function scopeByHash($query, string $hash)
    {
        return $query->where('file_hash', $hash);
    }

    /**
     * 查询作用域 - 按存储驱动筛选
     */
    public function scopeByDisk($query, string $disk)
    {
        return $query->where('storage_disk', $disk);
    }

    /**
     * 获取格式化的文件大小
     */
    public function getFormattedSizeAttribute(): string
    {
        return $this->formatBytes($this->file_size);
    }

    /**
     * 检查文件是否存在于存储中
     */
    public function exists(): bool
    {
        return Storage::disk($this->storage_disk)->exists($this->storage_path);
    }

    /**
     * 获取文件访问 URL
     */
    public function getUrlAttribute(): string
    {
        return app(\App\Services\FileUploadService::class)->generateFileUrl($this->storage_path, $this->storage_disk);
    }

    /**
     * 格式化字节数为可读格式
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}