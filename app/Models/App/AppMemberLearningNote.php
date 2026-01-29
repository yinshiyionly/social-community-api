<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppMemberLearningNote extends Model
{
    use HasFactory;

    protected $table = 'app_member_learning_note';
    protected $primaryKey = 'note_id';
    public $timestamps = false;

    const DEL_FLAG_NORMAL = 0;
    const DEL_FLAG_DELETED = 1;

    protected $fillable = [
        'member_id',
        'course_id',
        'chapter_id',
        'time_point',
        'content',
        'images',
        'is_public',
        'like_count',
        'create_time',
        'update_time',
        'del_flag',
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
        'del_flag' => 'integer',
        'create_time' => 'datetime',
        'update_time' => 'datetime',
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
        return $query->where('member_id', $memberId)
                     ->where('del_flag', self::DEL_FLAG_NORMAL);
    }

    /**
     * 查询作用域：公开笔记
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', 1)
                     ->where('del_flag', self::DEL_FLAG_NORMAL);
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
