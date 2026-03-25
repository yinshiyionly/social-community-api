<?php

namespace App\Services\App;

use App\Models\App\AppChapterHomework;
use App\Models\App\AppCourseChapter;
use App\Models\App\AppMemberChapterProgress;
use App\Models\App\AppMemberCourse;
use App\Models\App\AppMemberHomeworkSubmit;
use App\Models\App\AppMemberSchedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LearningCenterService
{
    /**
     * 生成用户课表
     *
     * 根据课程上架章节全量重建用户章节课表。
     *
     * 关键规则：
     * 1. 先物理清理该用户该课程下的章节课表（含软删记录），避免唯一索引冲突与历史脏数据残留；
     * 2. 再按章节 chapter_start_time 的相对天数间隔平移到报名日期；
     * 3. unlock_type/unlock_days/unlock_date 在入课表阶段统一忽略；
     * 4. unlock_time 维持历史口径（仍记录 chapter_start_time），避免扩大本次改造影响面。
     *
     * @param int $memberId 用户ID
     * @param int $courseId 课程ID
     * @param int $memberCourseId 用户课程记录ID
     * @param \DateTime $enrollDate 报名日期（作为章节日期平移基准）
     * @return void
     * @throws \Exception
     */
    public function generateSchedule(int $memberId, int $courseId, int $memberCourseId, \DateTime $enrollDate): void
    {
        try {
            DB::transaction(function () use ($memberId, $courseId, $memberCourseId, $enrollDate) {
                // 每次发课都先清理该用户该课程的章节课表（含软删），确保按最新规则重建且不触发唯一键冲突。
                AppMemberSchedule::withTrashed()
                    ->where('member_id', $memberId)
                    ->where('course_id', $courseId)
                    ->where('biz_type', AppMemberSchedule::BIZ_TYPE_CHAPTER)
                    ->forceDelete();

                $chapters = AppCourseChapter::byCourse($courseId)
                    ->online()
                    ->orderBy('sort_order')
                    ->orderBy('chapter_id')
                    ->get();

                if ($chapters->isEmpty()) {
                    return;
                }

                $enrollBaseDate = Carbon::make($enrollDate);
                if (!$enrollBaseDate) {
                    throw new \Exception('报名时间无效，无法生成课表');
                }
                $enrollBaseDate = $enrollBaseDate->copy()->startOfDay();

                $baseChapterDate = $this->resolveEarliestChapterDate($chapters, $courseId);
                $today = date('Y-m-d');

                foreach ($chapters as $chapter) {
                    $chapterStartAt = $this->resolveChapterStartAtOrFail($chapter, $courseId);
                    $scheduleAt = $this->resolveScheduleAtByRelativeDays(
                        $chapterStartAt,
                        $baseChapterDate,
                        $enrollBaseDate
                    );

                    $scheduleDate = $scheduleAt->format('Y-m-d');
                    $isUnlocked = $scheduleDate <= $today ? 1 : 0;

                    AppMemberSchedule::create([
                        'member_id'        => $memberId,
                        'biz_type'         => AppMemberSchedule::BIZ_TYPE_CHAPTER,
                        'course_id'        => $courseId,
                        'chapter_id'       => $chapter->chapter_id,
                        'member_course_id' => $memberCourseId,
                        'schedule_date'    => $scheduleDate,
                        // 课表时间统一保留到分钟，秒位固定写 00，避免前端展示出现秒级抖动。
                        'schedule_time'    => $scheduleAt->format('H:i:00'),
                        'is_unlocked'      => $isUnlocked,
                        'unlock_time'      => $chapterStartAt->toDateTimeString()
                        // 'unlock_time'      => $isUnlocked ? now() : null,
                    ]);
                }
            });
        } catch (\Exception $e) {
            Log::error('课表生成失败', [
                'member_id'        => $memberId,
                'course_id'        => $courseId,
                'member_course_id' => $memberCourseId,
                'error'            => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * 计算课程章节最早日期（用于相对天数平移基准）。
     *
     * @param Collection<int, AppCourseChapter> $chapters
     * @param int $courseId 课程ID（用于错误日志）
     * @return Carbon
     * @throws \Exception
     */
    private function resolveEarliestChapterDate(Collection $chapters, int $courseId): Carbon
    {
        $baseDate = null;

        foreach ($chapters as $chapter) {
            $chapterStartAt = $this->resolveChapterStartAtOrFail($chapter, $courseId);
            $chapterDate = $chapterStartAt->copy()->startOfDay();

            if ($baseDate === null || $chapterDate->lt($baseDate)) {
                $baseDate = $chapterDate;
            }
        }

        // 理论上到达这里时 baseDate 一定存在，额外兜底避免后续空指针。
        if (!$baseDate) {
            throw new \Exception('课程缺少可排课章节，无法生成课表');
        }

        return $baseDate;
    }

    /**
     * 解析章节开始时间，缺失时直接失败回滚整次排课。
     *
     * @param AppCourseChapter $chapter
     * @param int $courseId 课程ID（用于异常上下文）
     * @return Carbon
     * @throws \Exception
     */
    private function resolveChapterStartAtOrFail(AppCourseChapter $chapter, int $courseId): Carbon
    {
        $chapterStartAt = Carbon::make($chapter->chapter_start_time);
        if (!$chapterStartAt) {
            throw new \Exception(sprintf(
                '章节开始时间缺失，无法生成课表：course_id=%d, chapter_id=%d',
                $courseId,
                (int)$chapter->chapter_id
            ));
        }

        return $chapterStartAt->copy()->startOfMinute();
    }

    /**
     * 按“章节原始日期相对间隔”将课表平移到报名日期。
     *
     * @param Carbon $chapterStartAt 当前章节开始时间（已对齐到分钟）
     * @param Carbon $baseChapterDate 全课程最早章节日期（00:00:00）
     * @param Carbon $enrollBaseDate 报名日期（00:00:00）
     * @return Carbon
     */
    private function resolveScheduleAtByRelativeDays(
        Carbon $chapterStartAt,
        Carbon $baseChapterDate,
        Carbon $enrollBaseDate
    ): Carbon {
        $chapterDate = $chapterStartAt->copy()->startOfDay();
        $offsetDays = $baseChapterDate->diffInDays($chapterDate, false);

        // 仅平移“日期”部分，章节原始时分沿用 chapter_start_time。
        return $enrollBaseDate->copy()
            ->addDays($offsetDays)
            ->setTime((int)$chapterStartAt->format('H'), (int)$chapterStartAt->format('i'), 0);
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
        // 课表Tab（日视图）当前仅展示章节排课，避免直播预约课表混入旧页面。
        $schedules = AppMemberSchedule::byMember($memberId)
            ->chapterBiz()
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
        // 周概览红点沿用章节课表口径，直播预约仅在课程Tab的 today-tasks/sections 展示。
        $counts = AppMemberSchedule::byMember($memberId)
            ->chapterBiz()
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
                'date'  => $dateStr,
                'count' => isset($counts[$dateStr]) ? (int)$counts[$dateStr] : 0,
            ];
            $current->modify('+1 day');
        }

        return $result;
    }

    /**
     * 获取课表区间数据（日期分组 + 日历红点）
     *
     * @param int $memberId 用户ID
     * @param string $startDate 起始日期 Y-m-d
     * @param string $endDate 结束日期 Y-m-d
     * @return array
     */
    public function getScheduleRange(int $memberId, string $startDate, string $endDate): array
    {
        // 查询区间内的课表记录，预加载章节、课程、作业
        // 区间课表接口保持章节维度，防止已上线前端出现直播空卡片。
        $schedules = AppMemberSchedule::byMember($memberId)
            ->chapterBiz()
            ->whereBetween('schedule_date', [$startDate, $endDate])
            ->select([
                'id',
                'course_id',
                'chapter_id',
                'member_course_id',
                'schedule_date',
                'schedule_time',
                'is_unlocked',
                'is_learned',
            ])
            ->with([
                'chapter:chapter_id,course_id,chapter_title,chapter_no,cover_image,has_homework,sort_order',
                'chapter.homeworks' => function ($query) {
                    $query->enabled()
                        ->select(['homework_id', 'chapter_id', 'course_id', 'homework_title'])
                        ->orderBy('sort_order');
                },
                'course:course_id,course_title,cover_image',
                'memberCourse:id,progress,learned_chapters,total_chapters',
            ])
            ->orderBy('schedule_date')
            ->orderBy('schedule_time')
            ->get();

        // 查询用户在这些章节的学习进度（批量查询避免 N+1）
        $chapterIds = $schedules->pluck('chapter_id')->unique()->filter()->values()->toArray();
        $progressMap = [];
        if (!empty($chapterIds)) {
            $progressMap = AppMemberChapterProgress::byMember($memberId)
                ->whereIn('chapter_id', $chapterIds)
                ->select(['chapter_id', 'progress', 'is_completed'])
                ->get()
                ->keyBy('chapter_id')
                ->toArray();
        }

        // 查询用户在这些章节的作业提交情况（批量查询）
        $homeworkIds = [];
        foreach ($schedules as $schedule) {
            if ($schedule->chapter && $schedule->chapter->homeworks) {
                foreach ($schedule->chapter->homeworks as $hw) {
                    $homeworkIds[] = $hw->homework_id;
                }
            }
        }
        $homeworkIds = array_unique($homeworkIds);

        $submittedHomeworkIds = [];
        if (!empty($homeworkIds)) {
            $submittedHomeworkIds = AppMemberHomeworkSubmit::byMember($memberId)
                ->whereIn('homework_id', $homeworkIds)
                ->pluck('homework_id')
                ->toArray();
        }

        // 按日期分组
        $grouped = $schedules->groupBy(function ($schedule) {
            return $schedule->schedule_date ? $schedule->schedule_date->format('Y-m-d') : '';
        });

        // 构建 marks 和 sections
        $marks = [];
        $sections = [];

        $current = new \DateTime($startDate);
        $end = new \DateTime($endDate);

        while ($current <= $end) {
            $dateStr = $current->format('Y-m-d');
            $daySchedules = isset($grouped[$dateStr]) ? $grouped[$dateStr] : collect();
            $hasSchedule = $daySchedules->isNotEmpty();

            $marks[] = [
                'date'        => $dateStr,
                'hasSchedule' => $hasSchedule,
            ];

            if ($hasSchedule) {
                $list = [];
                foreach ($daySchedules as $schedule) {
                    $list[] = $this->formatScheduleItem($schedule, $progressMap, $submittedHomeworkIds);
                }
                $sections[] = [
                    'date' => $dateStr,
                    'list' => $list,
                ];
            }

            $current->modify('+1 day');
        }

        return [
            'startDate' => $startDate,
            'endDate'   => $endDate,
            'marks'     => $marks,
            'sections'  => $sections,
        ];
    }

    /**
     * 格式化单个课表项
     *
     * @param AppMemberSchedule $schedule
     * @param array $progressMap
     * @param array $submittedHomeworkIds
     * @return array
     */
    private function formatScheduleItem(AppMemberSchedule $schedule, array $progressMap, array $submittedHomeworkIds): array
    {
        $chapter = $schedule->chapter;
        $course = $schedule->course;

        // 进度文案
        $progressText = '未学习';
        if ($schedule->is_learned) {
            $progressText = '已学完';
        } elseif (isset($progressMap[$schedule->chapter_id])) {
            $progress = $progressMap[$schedule->chapter_id];
            $pct = (int)$progress['progress'];
            $progressText = $pct > 0 ? '已学' . $pct . '%' : '未学习';
        }

        // 按钮文案
        $actionText = '去学习';
        $actionType = 'learn';
        if ($schedule->is_learned) {
            $actionText = '已学完';
            $actionType = 'view';
        }

        $item = [
            'id'           => $schedule->id,
            'type'         => 'course',
            'time'         => $schedule->schedule_time ? Carbon::make($schedule->schedule_time)->format('H:i') : '',
            'title'        => $chapter ? $chapter->chapter_title : '',
            'cover'        => $course ? $course->cover_image : '',
            'progressText' => $progressText,
            'actionText'   => $actionText,
            'actionType'   => $actionType,
            'bizId'        => $schedule->course_id,
        ];

        // 关联打卡任务（取章节第一个启用的作业）
        if ($chapter && $chapter->has_homework && $chapter->homeworks && $chapter->homeworks->isNotEmpty()) {
            $homework = $chapter->homeworks->first();
            $isSubmitted = in_array($homework->homework_id, $submittedHomeworkIds);

            $item['checkinTask'] = [
                'id'         => $homework->homework_id,
                'title'      => '打卡：' . $homework->homework_title,
                'actionText' => $isSubmitted ? '已完成' : '去完成',
                'actionType' => $isSubmitted ? 'view' : 'task',
                'bizId'      => $homework->homework_id,
            ];
        }

        return $item;
    }


}
