<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppChapterContentAudio extends Model
{
    use HasFactory;

    protected $table = 'app_chapter_content_audio';
    protected $primaryKey = 'id';
    public $timestamps = false;

    // 音频来源
    const SOURCE_LOCAL = 'local';
    const SOURCE_ALIYUN = 'aliyun';
    const SOURCE_TENCENT = 'tencent';

    protected $fillable = [
        'chapter_id',
        'audio_url',
        'audio_id',
        'audio_source',
        'duration',
        'file_size',
        'cover_image',
        'transcript',
        'timeline_text',
        'attachments',
        'allow_download',
        'background_play',
        'create_time',
        'update_time',
    ];

    protected $casts = [
        'id' => 'integer',
        'chapter_id' => 'integer',
        'duration' => 'integer',
        'file_size' => 'integer',
        'timeline_text' => 'array',
        'attachments' => 'array',
        'allow_download' => 'integer',
        'background_play' => 'integer',
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
     * 根据时间点获取文字
     */
    public function getTextAtTime(int $seconds): ?string
    {
        if (empty($this->timeline_text)) {
            return null;
        }

        foreach ($this->timeline_text as $item) {
            if ($seconds >= $item['start'] && $seconds < $item['end']) {
                return $item['text'];
            }
        }

        return null;
    }
}
