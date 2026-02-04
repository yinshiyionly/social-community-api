<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppMemberSchedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'app_member_schedule';
    protected $primaryKey = 'id';

    protected $fillable = [
        'member_id',
        'course_id',
        'chapter_id',
        'member_course_id',
        'schedule_date',
        'schedule_time',
        'is_unlocked',
        'unlock_time',
        'is_learned',
        'learn_time',
        'is_notified',
    ];

    protected $casts = [
        'id' => 'integer',
        'member_id' => 'integer',
        'course_id' => 'integer',
        'chapter_id' => 'integer',
        'member_course_id' => 'integer',
        'schedule_date' => 'date',
        'is_unlocked' => 'integer',
        'unlock_time' => 'datetime',
        'is_learned' => 'integer',
        'learn_time' => 'datetime',
        'is_notified' => 'integer',
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
     * 关联用户课程
     */
    public function memberCourse()
    {
        return $this->belongsTo(AppMemberCourse::class, 'member_course_id', 'id');
    }

    /**
     * 查询作用域：按用户
     */
    public function scopeByMember($query, int $memberId)
    {
        return $query->where('member_id', $memberId);
    }

    /**
     * 查询作用域：按日期
     */
    public function scopeByDate($query, string $date)
    {
        return $query->where('schedule_date', $date);
    }

    /**
     * 查询作用域：今日
     */
    public function scopeToday($query)
    {
        return $query->where('schedule_date', date('Y-m-d'));
    }

    /**
     * 查询作用域：已解锁
     */
    public function scopeUnlocked($query)
    {
        return $query->where('is_unlocked', 1);
    }

    /**
     * 查询作用域：待解锁
     */
    public function scopePendingUnlock($query)
    {
        return $query->where('is_unlocked', 0)
            ->where('schedule_date', '<=', date('Y-m-d'));
    }

    /**
     * 解锁章节
     */
    public function unlock(): bool
    {
        $this->is_unlocked = 1;
        $this->unlock_time = now();
        return $this->save();
    }

    /**
     * 标记已学习
     */
    public function markLearned(): bool
    {
        $this->is_learned = 1;
        $this->learn_time = now();
        return $this->save();
    }

    /**
     * 检查是否应该解锁
     */
    public function shouldUnlock(): bool
    {
        if ($this->is_unlocked) {
            return false;
        }

        $today = date('Y-m-d');
        $scheduleDate = $this->schedule_date->format('Y-m-d');

        return $scheduleDate <= $today;
    }

    /**
     * 批量生成用户课表
     */
    public static function generateSchedule(int $memberId, int $courseId, int $memberCourseId, \DateTime $enrollDate): void
    {
        $chapters = AppCourseChapter::byCourse($courseId)
            ->online()
            ->orderBy('sort_order')
            ->get();

        foreach ($chapters as $chapter) {
            $scheduleDate = $chapter->calculateUnlockDate($enrollDate);
            if (!$scheduleDate) {
                continue;
            }

            self::create([
                'member_id' => $memberId,
                'course_id' => $courseId,
                'chapter_id' => $chapter->chapter_id,
                'member_course_id' => $memberCourseId,
                'schedule_date' => $scheduleDate,
                'schedule_time' => $chapter->unlock_time,
                'is_unlocked' => $scheduleDate <= date('Y-m-d') ? 1 : 0,
                'unlock_time' => $scheduleDate <= date('Y-m-d') ? now() : null,
            ]);
        }
    }
}
