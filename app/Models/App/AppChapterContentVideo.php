<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppChapterContentVideo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'app_chapter_content_video';
    protected $primaryKey = 'id';

    // 视频来源
    const SOURCE_LOCAL = 'local';
    const SOURCE_ALIYUN = 'aliyun';
    const SOURCE_TENCENT = 'tencent';
    const SOURCE_VOLCENGINE = 'volcengine';

    protected $fillable = [
        'chapter_id',
        'video_url',
        'video_id',
        'video_source',
        'duration',
        'width',
        'height',
        'file_size',
        'cover_image',
        'quality_list',
        'subtitles',
        'attachments',
        'allow_download',
        'drm_enabled',
    ];

    protected $casts = [
        'id' => 'integer',
        'chapter_id' => 'integer',
        'duration' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'file_size' => 'integer',
        'quality_list' => 'array',
        'subtitles' => 'array',
        'attachments' => 'array',
        'allow_download' => 'integer',
        'drm_enabled' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 关联章节
     */
    public function chapter()
    {
        return $this->belongsTo(AppCourseChapter::class, 'chapter_id', 'chapter_id');
    }

    /**
     * 获取格式化时长
     */
    public function getFormattedDurationAttribute(): string
    {
        $hours = floor($this->duration / 3600);
        $minutes = floor(($this->duration % 3600) / 60);
        $seconds = $this->duration % 60;

        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    /**
     * 获取格式化文件大小
     */
    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $index = 0;

        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }

        return round($bytes, 2) . ' ' . $units[$index];
    }
}
