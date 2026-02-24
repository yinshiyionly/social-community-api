<?php

namespace App\Services\App;

use App\Models\App\AppCourseChapter;
use App\Models\App\AppMemberCourse;
use App\Models\App\AppMemberSchedule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LearningCenterService
{
    /**
     * 生成用户课表
     *
     * 根据课程的上架章节和解锁规则，为用户生成 app_member_schedule 记录。
     * 使用事务包裹，异常时自动回滚并记录日志。
     *
     * @param int $memberId 用户ID
     * @param int $courseId 课程ID
     * @param int $memberCourseId 用户课程记录ID
     * @param \DateTime $enrollDate 报名日期
     * @return void
     * @throws \Exception
     */
    public function generateSchedule(int $memberId, int $courseId, int $memberCourseId, \DateTime $enrollDate): void
    {
        $chapters = AppCourseChapter::byCourse($courseId)
            ->online()
            ->orderBy('sort_order')
            ->get();

        if ($chapters->isEmpty()) {
            return;
        }

        $today = date('Y-m-d');

        try {
            DB::transaction(function () use ($memberId, $courseId, $memberCourseId, $enrollDate, $chapters, $today) {
                foreach ($chapters as $chapter) {
                    $scheduleDate = $chapter->calculateUnlockDate($enrollDate);
                    if (!$scheduleDate) {
                        continue;
                    }

                    $isUnlocked = $scheduleDate <= $today ? 1 : 0;

                    AppMemberSchedule::create([
                        'member_id' => $memberId,
                        'course_id' => $courseId,
                        'chapter_id' => $chapter->chapter_id,
                        'member_course_id' => $memberCourseId,
                        'schedule_date' => $scheduleDate,
                        'schedule_time' => $chapter->unlock_time,
                        'is_unlocked' => $isUnlocked,
                        'unlock_time' => $isUnlocked ? now() : null,
                    ]);
                }
            });
        } catch (\Exception $e) {
            Log::error('课表生成失败', [
                'member_id' => $memberId,
                'course_id' => $courseId,
                'member_course_id' => $memberCourseId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * 获取用户课程列表
     *
     * 查询用户所有未过期的课程记录，预加载课程基础信息，
     * 按最后学习时间降序排列（未学习过的排在后面，按报名时间降序）。
     *
     * @param int $memberId 用户ID
     * @return Collection
     */
    public function getMyCourses(int $memberId): Collection
    {
        return AppMemberCourse::byMember($memberId)
            ->notExpired()
            ->select([
                'id',
                'course_id',
                'progress',
                'learned_chapters',
                'total_chapters',
                'is_completed',
                'last_learn_time',
                'enroll_time',
            ])
            ->with(['course:course_id,course_title,cover_image'])
            ->orderByRaw('last_learn_time DESC NULLS LAST')
            ->orderBy('enroll_time', 'desc')
            ->get();
    }

    /**
     * 获取课表日视图数据
     *
     * 查询指定用户在指定日期的所有课表记录，预加载章节信息，
     * 按章节的 sort_order 升序排列。
     *
     * @param int $memberId 用户ID
     * @param string $date 日期，格式 Y-m-d
     * @return Collection
     */
    public function getDailySchedule(int $memberId, string $date): Collection
    {
        $schedules = AppMemberSchedule::byMember($memberId)
            ->byDate($date)
            ->select([
                'id',
                'course_id',
                'chapter_id',
                'schedule_date',
                'schedule_time',
                'is_unlocked',
                'is_learned',
            ])
            ->with(['chapter:chapter_id,chapter_title,chapter_no,cover_image,has_homework,sort_order'])
            ->get();

        return $schedules->sortBy(function ($schedule) {
            return $schedule->chapter ? $schedule->chapter->sort_order : 0;
        })->values();
    }

    /**
     * 获取课表周概览
     *
     * 查询指定日期范围内每天的课表记录数量，
     * 补全无记录的天数（count=0）。
     *
     * @param int $memberId 用户ID
     * @param string $startDate 起始日期，格式 Y-m-d
     * @param string $endDate 结束日期，格式 Y-m-d
     * @return array [['date' => '2026-01-05', 'count' => 3], ...]
     */
    public function getWeekOverview(int $memberId, string $startDate, string $endDate): array
    {
        $counts = AppMemberSchedule::byMember($memberId)
            ->whereBetween('schedule_date', [$startDate, $endDate])
            ->selectRaw("to_char(schedule_date, 'YYYY-MM-DD') as date, COUNT(*) as count")
            ->groupBy('schedule_date')
            ->pluck('count', 'date')
            ->toArray();

        $result = [];
        $current = new \DateTime($startDate);
        $end = new \DateTime($endDate);

        while ($current <= $end) {
            $dateStr = $current->format('Y-m-d');
            $result[] = [
                'date' => $dateStr,
                'count' => isset($counts[$dateStr]) ? (int) $counts[$dateStr] : 0,
            ];
            $current->modify('+1 day');
        }

        return $result;
    }



}
