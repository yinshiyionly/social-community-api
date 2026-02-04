<?php

namespace App\Models\App;

use App\Models\Traits\HasOperator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppCourseChapter extends Model
{
    use HasFactory, SoftDeletes, HasOperator;

    protected $table = 'app_course_chapter';
    protected $primaryKey = 'chapter_id';

    // 解锁类型
    const UNLOCK_TYPE_IMMEDIATE = 1;  // 立即解锁
    const UNLOCK_TYPE_DAYS = 2;       // 按天数解锁
    const UNLOCK_TYPE_DATE = 3;       // 按日期解锁

    // 状态
    const STATUS_DRAFT = 0;
    const STATUS_ONLINE = 1;
    const STATUS_OFFLINE = 2;

    protected $fillable = [
        'course_id',
        'chapter_no',
        'chapter_title',
        'chapter_subtitle',
        'cover_image',
        'brief',
        'is_free',
        'is_preview',
        'unlock_type',
        'unlock_days',
        'unlock_date',
        'unlock_time',
        'has_homework',
        'homework_required',
        'duration',
        'min_learn_time',
        'allow_skip',
        'allow_speed',
        'view_count',
        'complete_count',
        'homework_count',
        'sort_order',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'chapter_id' => 'integer',
        'course_id' => 'integer',
        'chapter_no' => 'integer',
        'is_free' => 'integer',
        'is_preview' => 'integer',
        'unlock_type' => 'integer',
        'unlock_days' => 'integer',
        'unlock_date' => 'date',
        'has_homework' => 'integer',
        'homework_required' => 'integer',
        'duration' => 'integer',
        'min_learn_time' => 'integer',
        'allow_skip' => 'integer',
        'allow_speed' => 'integer',
        'view_count' => 'integer',
        'complete_count' => 'integer',
        'homework_count' => 'integer',
        'sort_order' => 'integer',
        'status' => 'integer',
        'created_at' => 'datetime',
        'created_by' => 'integer',
        'updated_at' => 'datetime',
        'updated_by' => 'integer',
        'deleted_at' => 'datetime',
        'deleted_by' => 'integer',
    ];

    /**
     * 关联课程
     */
    public function course()
    {
        return $this->belongsTo(AppCourseBase::class, 'course_id', 'course_id');
    }

    /**
     * 关联作业
     */
    public function homeworks()
    {
        return $this->hasMany(AppChapterHomework::class, 'chapter_id', 'chapter_id');
    }

    /**
     * 关联图文内容
     */
    public function articleContent()
    {
        return $this->hasOne(AppChapterContentArticle::class, 'chapter_id', 'chapter_id');
    }

    /**
     * 关联视频内容
     */
    public function videoContent()
    {
        return $this->hasOne(AppChapterContentVideo::class, 'chapter_id', 'chapter_id');
    }

    /**
     * 关联直播内容
     */
    public function liveContent()
    {
        return $this->hasOne(AppChapterContentLive::class, 'chapter_id', 'chapter_id');
    }

    /**
     * 关联音频内容
     */
    public function audioContent()
    {
        return $this->hasOne(AppChapterContentAudio::class, 'chapter_id', 'chapter_id');
    }

    /**
     * 获取章节内容（根据课程类型）
     */
    public function getContent()
    {
        $course = $this->course;
        if (!$course) {
            return null;
        }

        switch ($course->play_type) {
            case AppCourseBase::PLAY_TYPE_ARTICLE:
                return $this->articleContent;
            case AppCourseBase::PLAY_TYPE_VIDEO:
                return $this->videoContent;
            case AppCourseBase::PLAY_TYPE_LIVE:
                return $this->liveContent;
            case AppCourseBase::PLAY_TYPE_AUDIO:
                return $this->audioContent;
            default:
                return null;
        }
    }

    /**
     * 查询作用域：上架状态
     */
    public function scopeOnline($query)
    {
        return $query->where('status', self::STATUS_ONLINE);
    }

    /**
     * 查询作用域：按课程
     */
    public function scopeByCourse($query, int $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    /**
     * 查询作用域：免费章节
     */
    public function scopeFree($query)
    {
        return $query->where('is_free', 1);
    }

    /**
     * 查询作用域：先导课
     */
    public function scopePreview($query)
    {
        return $query->where('is_preview', 1);
    }

    /**
     * 是否可免费观看
     */
    public function isFreeToWatch(): bool
    {
        return $this->is_free === 1 || $this->is_preview === 1;
    }

    /**
     * 计算解锁日期（动态解锁模式）
     */
    public function calculateUnlockDate(\DateTime $enrollDate): ?string
    {
        if ($this->unlock_type === self::UNLOCK_TYPE_IMMEDIATE) {
            return $enrollDate->format('Y-m-d');
        }

        if ($this->unlock_type === self::UNLOCK_TYPE_DAYS) {
            return (clone $enrollDate)->modify("+{$this->unlock_days} days")->format('Y-m-d');
        }

        if ($this->unlock_type === self::UNLOCK_TYPE_DATE) {
            return $this->unlock_date ? $this->unlock_date->format('Y-m-d') : null;
        }

        return null;
    }
}
