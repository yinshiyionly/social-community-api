<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppMemberHomeworkSubmit extends Model
{
    use HasFactory;

    protected $table = 'app_member_homework_submit';
    protected $primaryKey = 'submit_id';
    public $timestamps = false;

    // 批改状态
    const REVIEW_STATUS_PENDING = 0;   // 待批改
    const REVIEW_STATUS_PASSED = 1;    // 已通过
    const REVIEW_STATUS_REVISE = 2;    // 需修改

    protected $fillable = [
        'homework_id',
        'chapter_id',
        'course_id',
        'member_id',
        'submit_content',
        'submit_images',
        'submit_videos',
        'submit_files',
        'review_status',
        'review_content',
        'reviewer_id',
        'review_time',
        'point_earned',
        'like_count',
        'comment_count',
        'is_excellent',
        'client_ip',
        'create_time',
        'update_time',
    ];

    protected $casts = [
        'submit_id' => 'integer',
        'homework_id' => 'integer',
        'chapter_id' => 'integer',
        'course_id' => 'integer',
        'member_id' => 'integer',
        'submit_images' => 'array',
        'submit_videos' => 'array',
        'submit_files' => 'array',
        'review_status' => 'integer',
        'reviewer_id' => 'integer',
        'review_time' => 'datetime',
        'point_earned' => 'integer',
        'like_count' => 'integer',
        'comment_count' => 'integer',
        'is_excellent' => 'integer',
        'create_time' => 'datetime',
        'update_time' => 'datetime',
    ];

    /**
     * 关联作业
     */
    public function homework()
    {
        return $this->belongsTo(AppChapterHomework::class, 'homework_id', 'homework_id');
    }

    /**
     * 关联章节
     */
    public function chapter()
    {
        return $this->belongsTo(AppCourseChapter::class, 'chapter_id', 'chapter_id');
    }

    /**
     * 关联课程
     */
    public function course()
    {
        return $this->belongsTo(AppCourseBase::class, 'course_id', 'course_id');
    }

    /**
     * 关联用户
     */
    public function member()
    {
        return $this->belongsTo(AppMemberBase::class, 'member_id', 'member_id');
    }

    /**
     * 查询作用域：按用户
     */
    public function scopeByMember($query, int $memberId)
    {
        return $query->where('member_id', $memberId);
    }

    /**
     * 查询作用域：待批改
     */
    public function scopePending($query)
    {
        return $query->where('review_status', self::REVIEW_STATUS_PENDING);
    }

    /**
     * 查询作用域：优秀作业
     */
    public function scopeExcellent($query)
    {
        return $query->where('is_excellent', 1);
    }

    /**
     * 是否待批改
     */
    public function isPending(): bool
    {
        return $this->review_status === self::REVIEW_STATUS_PENDING;
    }

    /**
     * 是否已通过
     */
    public function isPassed(): bool
    {
        return $this->review_status === self::REVIEW_STATUS_PASSED;
    }

    /**
     * 批改通过
     */
    public function markPassed(?string $content = null, ?int $reviewerId = null): bool
    {
        $this->review_status = self::REVIEW_STATUS_PASSED;
        $this->review_content = $content;
        $this->reviewer_id = $reviewerId;
        $this->review_time = now();
        $this->update_time = now();
        return $this->save();
    }

    /**
     * 标记需修改
     */
    public function markRevise(string $content, ?int $reviewerId = null): bool
    {
        $this->review_status = self::REVIEW_STATUS_REVISE;
        $this->review_content = $content;
        $this->reviewer_id = $reviewerId;
        $this->review_time = now();
        $this->update_time = now();
        return $this->save();
    }
}
