<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminVideoChapterContent extends Model
{
    use HasFactory;

    protected $table = 'admin_video_chapter_content';
    protected $primaryKey = 'content_id';

    protected $fillable = [
        'chapter_id',
        'video_id',
        'sort_order',
    ];

    protected $casts = [
        'content_id' => 'integer',
        'chapter_id' => 'integer',
        'video_id' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * 所属章节
     */
    public function chapter()
    {
        return $this->belongsTo(AdminVideoChapter::class, 'chapter_id', 'chapter_id');
    }
}
