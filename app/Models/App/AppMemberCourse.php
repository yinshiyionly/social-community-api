<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppMemberCourse extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'app_member_course';
    protected $primaryKey = 'id';

    // 来源类型
    const SOURCE_TYPE_PURCHASE = 1;   // 购买
    const SOURCE_TYPE_FREE = 2;       // 免费领取
    const SOURCE_TYPE_EXCHANGE = 3;   // 兑换
    const SOURCE_TYPE_GIFT = 4;       // 赠送
    const SOURCE_TYPE_ACTIVITY = 5;   // 活动

    protected $fillable = [
        'member_id',
        'course_id',
        'order_no',
        'source_type',
        'promotion_id',
        'enroll_phone',
        'enroll_age_range',
        'paid_amount',
        'paid_points',
        'enroll_time',
        'expire_time',
        'is_expired',
        'learned_chapters',
        'total_chapters',
        'learned_duration',
        'progress',
        'last_chapter_id',
        'last_position',
        'last_learn_time',
        'is_completed',
        'complete_time',
        'homework_submitted',
        'homework_total',
        'checkin_days',
    ];

    protected $casts = [
        'id' => 'integer',
        'member_id' => 'integer',
        'course_id' => 'integer',
        'source_type' => 'integer',
        'promotion_id' => 'integer',
        'paid_amount' => 'decimal:2',
        'paid_points' => 'integer',
        'enroll_time' => 'datetime',
        'expire_time' => 'datetime',
        'is_expired' => 'integer',
        'learned_chapters' => 'integer',
        'total_chapters' => 'integer',
        'learned_duration' => 'integer',
        'progress' => 'decimal:2',
        'last_chapter_id' => 'integer',
        'last_position' => 'integer',
        'last_learn_time' => 'datetime',
        'is_completed' => 'integer',
        'complete_time' => 'datetime',
        'homework_submitted' => 'integer',
        'homework_total' => 'integer',
        'checkin_days' => 'integer',
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
     * 关联课表
     */
    public function schedules()
    {
        return $this->hasMany(AppMemberSchedule::class, 'member_course_id', 'id');
    }

    /**
     * 关联章节进度
     */
    public function chapterProgress()
    {
        return $this->hasMany(AppMemberChapterProgress::class, 'course_id', 'course_id')
            ->where('member_id', $this->member_id);
    }

    /**
     * 查询作用域：按用户
     */
    public function scopeByMember($query, int $memberId)
    {
        return $query->where('member_id', $memberId);
    }

    /**
     * 查询作用域：未过期
     */
    public function scopeNotExpired($query)
    {
        return $query->where('is_expired', 0);
    }

    /**
     * 查询作用域：已完课
     */
    public function scopeCompleted($query)
    {
        return $query->where('is_completed', 1);
    }

    /**
     * 查询作用域：学习中
     */
    public function scopeLearning($query)
    {
        return $query->where('is_expired', 0)->where('is_completed', 0);
    }

    /**
     * 检查用户是否已拥有课程
     */
    public static function hasCourse(int $memberId, int $courseId): bool
    {
        return self::byMember($memberId)
            ->where('course_id', $courseId)
            ->notExpired()
            ->exists();
    }

    /**
     * 获取用户课程
     */
    public static function getMemberCourse(int $memberId, int $courseId): ?self
    {
        return self::byMember($memberId)
            ->where('course_id', $courseId)
            ->notExpired()
            ->first();
    }

    /**
     * 更新学习进度
     */
    public function updateProgress(): void
    {
        if ($this->total_chapters > 0) {
            $this->progress = round(($this->learned_chapters / $this->total_chapters) * 100, 2);
        }

        if ($this->progress >= 100 && !$this->is_completed) {
            $this->is_completed = 1;
            $this->complete_time = now();
        }

        $this->save();
    }

    /**
     * 记录学习
     */
    public function recordLearn(int $chapterId, int $position = 0): void
    {
        $this->last_chapter_id = $chapterId;
        $this->last_position = $position;
        $this->last_learn_time = now();
        $this->save();
    }

    /**
     * 是否已过期
     */
    public function checkExpired(): bool
    {
        if ($this->expire_time && $this->expire_time < now()) {
            $this->is_expired = 1;
            $this->save();
            return true;
        }
        return false;
    }
}
