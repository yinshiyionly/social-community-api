<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdminVideoChapter extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'admin_video_chapter';
    protected $primaryKey = 'chapter_id';

    protected $fillable = [
        'course_id',
        'chapter_title',
        'unlock_time',
        'is_free_trial',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'chapter_id' => 'integer',
        'course_id' => 'integer',
        'is_free_trial' => 'integer',
        'status' => 'integer',
        'sort_order' => 'integer',
        'unlock_time' => 'datetime',
    ];

    // 状态常量
    const STATUS_ONLINE = 1;
    const STATUS_OFFLINE = 2;

    // 免费试看常量
    const FREE_TRIAL_YES = 1;
    const FREE_TRIAL_NO = 0;

    /**
     * 关联章节内容（视频）
     */
    public function contents()
    {
        return $this->hasMany(AdminVideoChapterContent::class, 'chapter_id', 'chapter_id');
    }

    /**
     * 查询作用域 - 上线状态
     */
    public function scopeOnline($query)
    {
        return $query->where('status', self::STATUS_ONLINE);
    }

    /**
     * 查询作用域 - 按课程筛选
     */
    public function scopeByCourse($query, int $courseId)
    {
        return $query->where('course_id', $courseId);
    }
}
