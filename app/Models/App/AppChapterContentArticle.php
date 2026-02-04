<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppChapterContentArticle extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'app_chapter_content_article';
    protected $primaryKey = 'id';

    protected $fillable = [
        'chapter_id',
        'content_html',
        'images',
        'attachments',
        'word_count',
        'read_time',
    ];

    protected $casts = [
        'id' => 'integer',
        'chapter_id' => 'integer',
        'images' => 'array',
        'attachments' => 'array',
        'word_count' => 'integer',
        'read_time' => 'integer',
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
     * 计算字数和阅读时间
     */
    public function calculateReadStats(): void
    {
        $text = strip_tags($this->content_html);
        $this->word_count = mb_strlen($text);
        // 按每分钟300字计算
        $this->read_time = (int)ceil($this->word_count / 300) * 60;
    }
}
