<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppMemberLearningCheckin extends Model
{
    use HasFactory;

    protected $table = 'app_member_learning_checkin';
    protected $primaryKey = 'id';
    public $timestamps = false;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'member_id',
        'course_id',
        'chapter_id',
        'checkin_date',
        'learn_duration',
        'chapters_learned',
        'summary',
        'images',
        'point_earned',
    ];

    protected $casts = [
        'id' => 'integer',
        'member_id' => 'integer',
        'course_id' => 'integer',
        'chapter_id' => 'integer',
        'checkin_date' => 'date',
        'learn_duration' => 'integer',
        'chapters_learned' => 'integer',
        'images' => 'array',
        'point_earned' => 'integer',
        'created_at' => 'datetime',
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
     * 查询作用域：按用户
     */
    public function scopeByMember($query, int $memberId)
    {
        return $query->where('member_id', $memberId);
    }

    /**
     * 查询作用域：按课程
     */
    public function scopeByCourse($query, int $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    /**
     * 查询作用域：今日
     */
    public function scopeToday($query)
    {
        return $query->where('checkin_date', date('Y-m-d'));
    }

    /**
     * 检查今日是否已打卡
     */
    public static function hasCheckedInToday(int $memberId, int $courseId): bool
    {
        return self::byMember($memberId)
            ->byCourse($courseId)
            ->today()
            ->exists();
    }

    /**
     * 获取连续打卡天数
     */
    public static function getContinuousDays(int $memberId, int $courseId): int
    {
        $records = self::byMember($memberId)
            ->byCourse($courseId)
            ->orderBy('checkin_date', 'desc')
            ->pluck('checkin_date')
            ->toArray();

        if (empty($records)) {
            return 0;
        }

        $days = 0;
        $expectedDate = date('Y-m-d');

        foreach ($records as $date) {
            $dateStr = $date instanceof \DateTime ? $date->format('Y-m-d') : $date;
            if ($dateStr === $expectedDate) {
                $days++;
                $expectedDate = date('Y-m-d', strtotime($expectedDate . ' -1 day'));
            } else {
                break;
            }
        }

        return $days;
    }
}
