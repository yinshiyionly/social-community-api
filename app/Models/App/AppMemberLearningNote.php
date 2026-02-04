<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppMemberLearningNote extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'app_member_learning_note';
    protected $primaryKey = 'note_id';

    protected $fillable = [
        'member_id',
        'course_id',
        'chapter_id',
        'time_point',
        'content',
        'images',
        'is_public',
        'like_count',
    ];

    protected $casts = [
        'note_id' => 'integer',
        'member_id' => 'integer',
        'course_id' => 'integer',
        'chapter_id' => 'integer',
        'time_point' => 'integer',
        'images' => 'array',
        'is_public' => 'integer',
        'like_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 关联用户
     */
    public function member()
    {
        return $this->belongsTo(AppMemberBase::class, 'member_id', 'member_id');
    }

    /**
     * 关联课程
     */
    public function course()
    {
        return $this->belongsTo(AppCourseBase::class, 'course_id', 'course_id');
    }

    /**
     * 关联章节
     */
    public function chapter()
    {
        return $this->belongsTo(AppCourseChapter::class, 'chapter_id', 'chapter_id');
    }

    /**
     * 查询作用域：按用户
     */
    public function scopeByMember($query, int $memberId)
    {
        return $query->where('member_id', $memberId);
    }

    /**
     * 查询作用域：公开笔记
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', 1);
    }

    /**
     * 获取格式化时间点
     */
    public function getFormattedTimePointAttribute(): string
    {
        $minutes = floor($this->time_point / 60);
        $seconds = $this->time_point % 60;
        return sprintf('%02d:%02d', $minutes, $seconds);
    }
}
