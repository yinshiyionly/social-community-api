<?php

namespace App\Services\App;

use App\Models\App\AppCourseChapter;
use App\Models\App\AppCourseBase;
use App\Models\App\AppCourseCategory;
use App\Models\App\AppChapterContentVideo;
use App\Models\App\AppMemberCourse;
use App\Models\App\AppMemberHomeworkSubmit;
use App\Models\App\AppMemberSchedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * 学习页（课程Tab）服务。
 *
 * 职责：
 * 1. 提供学习中心课程筛选、分组与列表查询；
 * 2. 聚合学习中心课程详情（课程头部 + 每日计划 tabs + 当日章节/作业项）；
 * 3. 提供章节学习上报流转（课表/章节进度/课程汇总）；
 * 4. 提供录播章节进度上报与续播点查询（hh:mm:ss 转秒入库）；
 * 5. 统一章节进度与作业状态文案，避免控制器重复拼装业务规则。
 */
class StudyCourseService
{
    /**
     * 课程卡片 statusText 计算场景：today-tasks。
     */
    private const STATUS_SCENE_TODAY = 'today';

    /**
     * 课程卡片 statusText 计算场景：sections/list。
     */
    private const STATUS_SCENE_GENERAL = 'general';

    /**
     * 获取课程分类筛选项
     *
     * @return array
     */
    public function getCourseFilters(): array
    {
        $categories = AppCourseCategory::enabled()
            ->topLevel()
            ->select(['category_id', 'category_name'])
            ->orderBy('sort_order')
            ->get();

        $result = [];
        foreach ($categories as $cat) {
            $result[] = [
                'label' => $cat->category_name,
                'value' => $cat->category_id,
            ];
        }

        return $result;
    }

    /**
     * 获取课程付费类型筛选项
     *
     * @return array
     */
    public function getCoursePayTypes(): array
    {
        $result = [];
        foreach (AppCourseBase::PAY_TYPE_CONFIG as $value => $config) {
            $result[] = [
                'label' => $config['typeName'],
                'value' => $value,
            ];
        }

        return $result;
    }

    /**
     * 获取学习中心课程基础信息。
     *
     * 查询约束：
     * 1. 仅按 course_id 查询，不限制课程上/下架；
     * 2. 软删课程返回 null；
     * 3. 主讲老师名称直接读取 app_course_base.teacher_name。
     *
     * @param int $courseId
     * @return AppCourseBase|null
     */
    public function getLearningCourseBase(int $courseId): ?AppCourseBase
    {
        return AppCourseBase::query()
            ->select([
                'course_id',
                'course_title',
                'play_type',
                'cover_image',
                'teacher_name',
                'class_teacher_name',
                'class_teacher_qr',
            ])
            ->where('course_id', $courseId)
            ->first();
    }

    /**
     * 判断用户是否已拥有课程（已领取/已购买且未过期）。
     *
     * @param int $memberId
     * @param int $courseId
     * @return bool
     */
    public function checkMemberHasCourse(int $memberId, int $courseId): bool
    {
        return AppMemberCourse::hasCourse($memberId, $courseId);
    }

    /**
     * 上报章节学习完成状态。
     *
     * 流转规则：
     * 1. 仅允许已拥有课程（未过期）的用户上报；
     * 2. 章节必须属于课程，且存在章节课表（biz_type=1）；
     * 3. 未解锁章节拒绝上报，防止绕过排课规则；
     * 4. 重复上报按幂等成功处理，但会刷新学习时间。
     *
     * @param int $memberId
     * @param int $courseId
     * @param int $chapterId
     * @return array{courseId:int, chapterId:int, isLearned:int, learnTime:string}
     */
    public function markChapterLearned(int $memberId, int $courseId, int $chapterId): array
    {
        $now = Carbon::now('Asia/Shanghai');

        return DB::transaction(function () use ($memberId, $courseId, $chapterId, $now) {
            $memberCourse = AppMemberCourse::query()
                ->byMember($memberId)
                ->notExpired()
                ->where('course_id', $courseId)
                ->lockForUpdate()
                ->first();

            if (!$memberCourse) {
                throw new \DomainException('course_not_owned');
            }

            $chapter = AppCourseChapter::query()
                ->select(['chapter_id', 'course_id', 'duration'])
                ->where('course_id', $courseId)
                ->where('chapter_id', $chapterId)
                ->first();

            if (!$chapter) {
                throw new \DomainException('chapter_not_found');
            }

            $schedule = AppMemberSchedule::query()
                ->byMember($memberId)
                ->chapterBiz()
                ->where('course_id', $courseId)
                ->where('chapter_id', $chapterId)
                ->lockForUpdate()
                ->first();

            if (!$schedule) {
                throw new \DomainException('schedule_not_found');
            }

            if ((int)$schedule->is_unlocked !== 1) {
                throw new \DomainException('schedule_not_unlocked');
            }

            // 幂等约束：重复上报仍刷新 learn_time，保证“最近学习”排序可感知最新学习行为。
            $schedule->is_learned = 1;
            $schedule->learn_time = $now->copy();

            $totalDuration = max(
                0,
                max((int)$schedule->total_duration, (int)$chapter->duration)
            );
            $schedule->total_duration = $totalDuration;
            $schedule->learned_duration = $totalDuration;
            $schedule->last_position = $totalDuration;
            $schedule->progress = 100;
            $schedule->is_completed = 1;
            $schedule->complete_time = $schedule->complete_time ?: $now->copy();
            $schedule->view_count = max(1, (int)$schedule->view_count);
            $schedule->first_view_time = $schedule->first_view_time ?: $now->copy();
            $schedule->last_view_time = $now->copy();
            $schedule->save();

            $this->syncMemberCourseLearnSnapshot($memberCourse, $memberId, $courseId, $chapterId, $now);

            return [
                'courseId'  => $courseId,
                'chapterId' => $chapterId,
                'isLearned' => 1,
                'learnTime' => $schedule->learn_time
                    ? $schedule->learn_time->format('Y-m-d H:i:s')
                    : $now->format('Y-m-d H:i:s'),
            ];
        });
    }

    /**
     * 同步用户课程汇总学习快照。
     *
     * 同步内容：
     * 1. learned_chapters：按章节课表 is_learned=1 统计；
     * 2. last_chapter_id / last_learn_time：记录本次学习落点；
     * 3. progress / is_completed / complete_time：按章节完成占比重算。
     *
     * @param AppMemberCourse $memberCourse
     * @param int $memberId
     * @param int $courseId
     * @param int $chapterId
     * @param Carbon $now
     * @return void
     */
    private function syncMemberCourseLearnSnapshot(
        AppMemberCourse $memberCourse,
        int             $memberId,
        int             $courseId,
        int             $chapterId,
        Carbon          $now
    ): void
    {
        $learnedChapters = AppMemberSchedule::query()
            ->byMember($memberId)
            ->chapterBiz()
            ->where('course_id', $courseId)
            ->where('is_learned', 1)
            ->count();

        $chapterTotalFromBase = (int)AppCourseChapter::query()
            ->where('course_id', $courseId)
            ->count();

        $totalChapters = max((int)$memberCourse->total_chapters, $chapterTotalFromBase);
        $progress = $totalChapters > 0
            ? round(min(100, ($learnedChapters / $totalChapters) * 100), 2)
            : 0;
        $isCompleted = $totalChapters > 0 && $progress >= 100;

        $memberCourse->learned_chapters = $learnedChapters;
        $memberCourse->total_chapters = $totalChapters;
        $memberCourse->last_chapter_id = $chapterId;
        $memberCourse->last_learn_time = $now->copy();
        $memberCourse->progress = $progress;
        $memberCourse->is_completed = $isCompleted ? 1 : 0;
        $memberCourse->complete_time = $isCompleted
            ? ($memberCourse->complete_time ?: $now->copy())
            : null;
        $memberCourse->save();
    }

    /**
     * 构建学习中心课程详情数据。
     *
     * 返回结构：
     * 1. 课程头部信息（标题、主讲、班主任）；
     * 2. 每日计划 tabs（先导课预习 + 课程全课表日）；
     * 3. 当前选中 tab 的章节/作业列表。
     *
     * @param int $memberId
     * @param AppCourseBase $course
     * @param string|null $planKey
     * @return array<string, mixed>
     */
    public function buildLearningCourseDetail(int $memberId, AppCourseBase $course, ?string $planKey = null): array
    {
        $courseId = (int)$course->course_id;
        $todayKey = Carbon::now('Asia/Shanghai')->format('Y-m-d');

        $previewChapters = $this->getPreviewChapters($courseId);

        $allSchedules = AppMemberSchedule::query()
            ->byMember($memberId)
            ->chapterBiz()
            ->where('course_id', $courseId)
            ->select(['id', 'chapter_id', 'schedule_date', 'schedule_time', 'is_learned'])
            ->orderBy('schedule_date')
            ->orderBy('schedule_time')
            ->orderBy('id')
            ->get();

        $dayKeys = $allSchedules
            ->map(function (AppMemberSchedule $schedule) {
                return $schedule->schedule_date ? $schedule->schedule_date->format('Y-m-d') : '';
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        $dateCountMap = $allSchedules
            ->groupBy(function (AppMemberSchedule $schedule) {
                return $schedule->schedule_date ? $schedule->schedule_date->format('Y-m-d') : '';
            })
            ->map(function (Collection $group) {
                return $group->count();
            })
            ->toArray();

        $availablePlanKeys = $dayKeys;
        if ($previewChapters->isNotEmpty()) {
            array_unshift($availablePlanKeys, 'preview');
        }

        $selectedPlanKey = $this->resolveSelectedPlanKey(
            $planKey,
            $todayKey,
            $availablePlanKeys,
            $this->extractUnlearnedDayKeys($allSchedules)
        );

        $items = [];
        if ($selectedPlanKey === 'preview') {
            $items = $this->buildPreviewPlanItems($memberId, $course, $previewChapters);
        } elseif ($selectedPlanKey !== '') {
            $items = $this->buildDayPlanItems($memberId, $course, $selectedPlanKey);
        }

        return [
            'course'    => [
                'courseId'         => $courseId,
                'courseTitle'      => (string)($course->course_title ?? ''),
                'lecturerName'     => (string)($course->teacher_name ?? ''),
                'classTeacherName' => (string)($course->class_teacher_name ?? ''),
                'classTeacherQr'   => (string)($course->class_teacher_qr ?? ''),
            ],
            'dailyPlan' => [
                'selectedPlanKey' => $selectedPlanKey,
                'todayPlanKey'    => in_array($todayKey, $dayKeys, true) ? $todayKey : '',
                'tabs'            => $this->buildDailyPlanTabs(
                    $previewChapters->isNotEmpty(),
                    $dayKeys,
                    $dateCountMap,
                    $todayKey
                ),
                'items'           => $items,
            ],
        ];
    }

    /**
     * 今日学习任务服务新实现
     *
     * @param int $memberId
     * @return array
     */
    public function newGetTodayTasks(int $memberId)
    {
        // 1. 获取今日的日期
        $today = Carbon::now('Asia/Shanghai')->format('Y-m-d');
        // 2. 在用户课表中查询今天的课程和章节数据
        $data = AppMemberSchedule::query()
            ->byMember($memberId)
            ->chapterBiz()
            ->select([
                'id',
                'course_id',
                'chapter_id',
                'schedule_date',
                'schedule_time',
                'is_learned',
                'learn_time',
            ])
            ->with([
                'course:course_id,course_title',
                'chapter:chapter_id,course_id,chapter_title,chapter_subtitle',
            ])
            ->where('schedule_date', $today)
            ->orderBy('schedule_date')
            ->orderBy('schedule_time')
            ->orderBy('id')
            ->limit(2)
            ->get()
            ->toArray();
        $result = [];
        foreach ($data as $item) {
            $result[] = [
                'id'           => $item['course_id'] ?? null,
                'time'         => Carbon::make($item['schedule_time'])->format('H:i'),
                'title'        => $item['course']['course_title'] ?? null,
                'subtitle'     => sprintf(
                    "%s: %s",
                    Carbon::make($item['schedule_date'])->format('m月d日'),
                    $item['chapter']['chapter_title'] ?? null
                ),
                'statusText'   => Carbon::make($item['schedule_time'])->gt(Carbon::now()) ? '待开课' : '去学习',
                'courseId'     => $item['course_id'] ?? null,
                'chapterId'    => $item['chapter_id'] ?? null,
                'scheduleDate' => $item['schedule_date'] ?? null,
                'scheduleId'   => $item['id'] ?? null
            ];
        }
        return $result;

        // 前端文档定义格式
        return [
            'id'           => 1,
            'time'         => '19:00',
            'title'        => '节气养正公开课',
            'subtitle'     => '小寒养生计划',
            'statusText'   => '待开课',
            // 第二版增加字段
            'courseId'     => 1,
            'chapterId'    => 1,
            'scheduleDate' => '2026-03-25',
            'scheduleId'   => 1
        ];

    }

    /**
     * 获取今日学习任务（课程维度）。
     *
     * 约束：
     * 1. 仅统计章节课表（biz_type=1），本次改造不混入直播预约；
     * 2. 同一课程当天可能有多条课表，返回时按“每课程1张卡”去重；
     * 3. 返回统一课程卡片字段，并保留旧字段用于前端兼容。
     *
     * @param int $memberId
     * @return array<int, array<string, mixed>>
     */
    public function getTodayTasks(int $memberId): array
    {
        $today = Carbon::now('Asia/Shanghai')->format('Y-m-d');
        $todaySchedules = $this->queryChapterSchedules($memberId, [], $today, true);
        if ($todaySchedules->isEmpty()) {
            return [];
        }

        $now = Carbon::now('Asia/Shanghai');
        $courseIds = $todaySchedules->pluck('course_id')->filter()->unique()->values()->all();
        $chapterMetaMap = $this->buildCourseChapterMetaMap($courseIds);
        $groupedSchedules = $this->groupSchedulesByCourse($todaySchedules);

        $todayTasks = [];
        foreach ($groupedSchedules as $courseId => $courseSchedules) {
            $course = $this->resolveCourseFromSchedules($courseSchedules);
            if (!$course) {
                continue;
            }

            $chapterMeta = $chapterMetaMap[$courseId] ?? $this->buildEmptyCourseChapterMeta();
            $representativeSchedule = $this->selectTodayRepresentativeSchedule($courseSchedules);
            $representativeChapter = $this->resolveRepresentativeChapter($representativeSchedule, $chapterMeta);
            $card = $this->buildCourseCardPayload(
                $course,
                $chapterMeta,
                $representativeSchedule,
                $representativeChapter,
                $now,
                self::STATUS_SCENE_TODAY
            );

            $todayTasks[] = array_merge($card, [
                // 兼容旧字段：time/subtitle 继续保留，避免前端一次性切换。
                'time'       => $this->formatScheduleTime(
                    $representativeSchedule ? (string)$representativeSchedule->schedule_time : null
                ),
                'subtitle'   => $representativeChapter ? (string)($representativeChapter->chapter_subtitle ?? '') : '',
                'scheduleId' => $representativeSchedule ? (int)($representativeSchedule->id ?? 0) : 0,
                'courseId'   => (int)($course->course_id ?? 0),
                'chapterId'  => $representativeSchedule ? (int)($representativeSchedule->chapter_id ?? 0) : 0,
                'liveId'     => 0,
                'bizType'    => 'chapter',
                'bizId'      => $representativeSchedule ? (int)($representativeSchedule->chapter_id ?? 0) : 0,
            ]);
        }

        return $todayTasks;
    }

    protected function formatCoursePayType($payType)
    {
        $list = [
            AppCourseBase::PAY_TYPE_TRIAL    => '招生0元课',
            AppCourseBase::PAY_TYPE_ADVANCED => '进阶课',
            AppCourseBase::PAY_TYPE_HIGHER   => '高阶课',
        ];
        return $list[$payType] ?? '招生0元课';
    }

    protected function formatStatusText()
    {

    }

    protected function formatActionText($data, $chapterTitle)
    {
        if (empty($data) || empty($data['learned_duration'])) {
            return '开始学习: ' . $chapterTitle;
        }
        if ($data['learned_duration'] > 0) {
            return '继续学习: ' . $chapterTitle;
        }
        if (isset($data['is_completed']) && $data['is_completed'] == 1) {
            return '已学完: ' . $chapterTitle;
        }
        return '开始学习: ' . $chapterTitle;
    }

    public function newGetCourseSections(int $memberId)
    {
        $now = Carbon::now();
        $today = $now->toDateString();
        $sectionLimit = 3;
        // 多取一批候选后再按课程去重，尽量让每个分组覆盖不同课程。
        $candidateLimit = $sectionLimit * 10;

        $result = [
            'recentSection'   => ['title' => '最近学习', 'list' => []],
            'pendingSection'  => ['title' => '待学习', 'list' => []],
            'finishedSection' => ['title' => '已结课', 'list' => []],
        ];

        /**
         * 公共查询字段
         */
        $baseSelect = [
            'id',
            'course_id',
            'chapter_id',
            'member_course_id',
            'schedule_date',
            'schedule_time',
            'is_learned',
            'learn_time',
            'learned_duration',
            'is_completed',
        ];

        $with = [
            'course:course_id,course_title,cover_image,pay_type',
            'chapter:chapter_id,course_id,chapter_title,chapter_subtitle,chapter_start_time,chapter_end_time',
            'memberCourse:id,course_id,total_chapters',
        ];

        /**
         * 1. 最近学习
         * 条件：learn_time 不为空，按 learn_time 倒序，取最近 3 条
         */
        $recentSchedules = AppMemberSchedule::query()
            ->byMember($memberId)
            ->chapterBiz()
            ->select($baseSelect)
            ->with($with)
            ->whereNotNull('learn_time')
            ->orderByDesc('learn_time')
            ->orderByDesc('id')
            ->limit($candidateLimit)
            ->get();
        $data['recentSection']['list'] = $this->pickSchedulesByDifferentCourses($recentSchedules, $sectionLimit)
            ->values()
            ->toArray();

        /**
         * 2. 待学习
         * 条件：is_learned = 0，按 schedule_date/schedule_time 离当前最近排序，取 3 条
         *
         * 一般理解为：
         * - 优先今天及未来的课
         * - 如果没有未来课，再补过去未学习的课
         */
        $pendingSchedules = AppMemberSchedule::query()
            ->byMember($memberId)
            ->chapterBiz()
            ->select($baseSelect)
            ->with($with)
            ->where('is_learned', 0)
            ->orderByRaw("
        CASE
            WHEN schedule_date >= ? THEN 0
            ELSE 1
        END ASC
    ", [$today])
            ->orderByRaw("
        ABS(
            EXTRACT(EPOCH FROM (
                (schedule_date::timestamp + schedule_time) - ?
            ))
        ) ASC
    ", [$now->toDateTimeString()])
            ->orderBy('id')
            ->limit($candidateLimit)
            ->get();
        $data['pendingSection']['list'] = $this->pickSchedulesByDifferentCourses($pendingSchedules, $sectionLimit)
            ->values()
            ->toArray();

        /**
         * 3. 已结课
         * 条件：关联章节 chapter_end_time，且当前时间 > chapter_end_time
         *
         * 注意：
         * 这里假设 chapter_end_time 是完整 datetime 字段
         * 如果它只是 time 字段，需要按你的实际业务再拼日期
         */
        $finishedSchedules = AppMemberSchedule::query()
            ->byMember($memberId)
            ->chapterBiz()
            ->select($baseSelect)
            ->with($with)
            ->whereHas('chapter', function ($query) use ($now) {
                $query->whereNotNull('chapter_end_time')
                    ->where('chapter_end_time', '<', $now);
            })
            ->orderByDesc('schedule_date')
            ->orderByDesc('schedule_time')
            ->orderByDesc('id')
            ->limit($candidateLimit)
            ->get();
        $data['finishedSection']['list'] = $this->pickSchedulesByDifferentCourses($finishedSchedules, $sectionLimit)
            ->values()
            ->toArray();


        foreach ($data['recentSection']['list'] as $item) {
            $result['recentSection']['list'][] = [
                'id'           => $item['course_id'] ?? null,
                'title'        => $item['course']['course_title'] ?? null,
                'cover'        => $item['course']['cover_image'] ?? null,
                'overlayText'  => sprintf(
                    "%s-共%s课",
                    $this->formatCoursePayType($item['course']['pay_type']),
                    $item['member_course']['total_chapters'] ?? 0
                ),
                // 暂时不知道什么用
                // 'timeText'     => $item['course']['course_title'] ?? null,
                // statusText 可能是 待开营 、已结营、2026.1.30 10:00上课
                'statusText'   => sprintf(
                    "%s上课",
                    Carbon::make($item['chapter']['chapter_start_time'])->timezone('Asia/Shanghai')->format('Y.m.d H:i')
                ),
                // actionText 可能是 开始学习：章节标题 继续学习：章节标题 已学完：章节标题
                'actionText'   => $this->formatActionText($item ?? [], $item['chapter']['chapter_title'] ?? ''), 'scheduleId' => $item['id'] ?? null,
                'courseId'     => $item['course_id'] ?? null,
                'chapterId'    => $item['chapter_id'] ?? null,
                'scheduleDate' => $item['schedule_date'] ?? null,
            ];
        }
        foreach ($data['pendingSection']['list'] as $item) {
            $result['pendingSection']['list'][] = [
                'id'           => $item['course_id'] ?? null,
                'title'        => $item['course']['course_title'] ?? null,
                'cover'        => $item['course']['cover_image'] ?? null,
                'overlayText'  => sprintf(
                    "%s-共%s课",
                    $this->formatCoursePayType($item['course']['pay_type']),
                    $item['member_course']['total_chapters'] ?? 0
                ),
                // 暂时不知道什么用
                // 'timeText'     => $item['course']['course_title'] ?? null,
                // statusText 可能是 待开营 、已结营、2026.1.30 10:00上课
                'statusText'   => sprintf(
                    "%s上课",
                    Carbon::make($item['chapter']['chapter_start_time'])->timezone('Asia/Shanghai')->format('Y.m.d H:i')
                ),
                // actionText 可能是 开始学习：章节标题 继续学习：章节标题 已学完：章节标题
                'actionText'   => $this->formatActionText($item ?? [], $item['chapter']['chapter_title'] ?? ''),
                'scheduleId'   => $item['id'] ?? null,
                'courseId'     => $item['course_id'] ?? null,
                'chapterId'    => $item['chapter_id'] ?? null,
                'scheduleDate' => $item['schedule_date'] ?? null,
            ];
        }
        foreach ($data['finishedSection']['list'] as $item) {
            $result['finishedSection']['list'][] = [
                'id'           => $item['course_id'] ?? null,
                'title'        => $item['course']['course_title'] ?? null,
                'cover'        => $item['course']['cover_image'] ?? null,
                'overlayText'  => sprintf(
                    "%s-共%s课",
                    $this->formatCoursePayType($item['course']['pay_type']),
                    $item['member_course']['total_chapters'] ?? 0
                ),
                // 暂时不知道什么用
                // 'timeText'     => $item['course']['course_title'] ?? null,
                // statusText 可能是 待开营 、已结营、2026.1.30 10:00上课
                'statusText'   => sprintf(
                    "%s上课",
                    Carbon::make($item['chapter']['chapter_start_time'])->timezone('Asia/Shanghai')->format('Y.m.d H:i')
                ),
                // actionText 可能是 开始学习：章节标题 继续学习：章节标题 已学完：章节标题
                'actionText'   => $this->formatActionText($item ?? [], $item['chapter']['chapter_title'] ?? ''), 'scheduleId' => $item['id'] ?? null,
                'courseId'     => $item['course_id'] ?? null,
                'chapterId'    => $item['chapter_id'] ?? null,
                'scheduleDate' => $item['schedule_date'] ?? null,
            ];
        }

        return $result;


        // 每个类型取最近三条数据即可

        // 1. 获取今日的日期
        // 2. 在用户课表中查询今天的课程和章节数据
        $data = AppMemberSchedule::query()
            ->byMember($memberId)
            ->chapterBiz()
            ->select([
                'id',
                'course_id',
                'chapter_id',
                'schedule_date',
                'schedule_time',
                'is_learned',
                'learn_time',
            ])
            ->with([
                'course:course_id,course_title',
                'chapter:chapter_id,course_id,chapter_title,chapter_subtitle',
            ])
            ->orderBy('schedule_date')
            ->orderBy('schedule_time')
            ->orderBy('id')
            ->get()
            ->toArray();
        $result = [];
        foreach ($data as $item) {
            $result[] = [
                'id'           => $item['course_id'] ?? null,
                'time'         => Carbon::make($item['schedule_time'])->format('H:i'),
                'title'        => $item['course']['course_title'] ?? null,
                'subtitle'     => sprintf(
                    "%s: %s",
                    Carbon::make($item['schedule_date'])->format('m月d日'),
                    $item['chapter']['chapter_title'] ?? null
                ),
                'statusText'   => Carbon::make($item['schedule_time'])->gt(Carbon::now()) ? '待开课' : '去学习',
                'courseId'     => $item['course_id'] ?? null,
                'chapterId'    => $item['chapter_id'] ?? null,
                'scheduleDate' => $item['schedule_date'] ?? null,
                'scheduleId'   => $item['id'] ?? null
            ];
        }

        return $result;

        [
            'id'          => 100000013,
            'title'       => '有章节-摄影课程主标题',
            'cover'       => 'https://dev-hobby-app.tos-cn-beijing.volces.com/admin/image/20260313/cacd21a1-9349-4ea7-b57a-ed975979d547.png',
            'overlayText' => '招生0元课-共12课',
            'timeText'    => '2026.03.17 09:44',
            'statusText'  => '已学完',
            'actionText'  => '已学完：章节：第一章节',
            'scheduleId'  => 128,
            'courseId'    => 100000013,
            'chapterId'   => 6,
            'liveId'      => 0,
            'bizType'     => 'chapter',
            'bizId'       => 6
        ];

        // $schedules = $this->queryChapterSchedules($memberId, [], null, true);
        if ($schedules->isEmpty()) {
            return [
                'recentSection'   => ['title' => '最近学习', 'list' => []],
                'pendingSection'  => ['title' => '待学习', 'list' => []],
                'finishedSection' => ['title' => '已结课', 'list' => []],
            ];
        }
    }

    /**
     * 优先选择不同课程的课表，数量不足时再回填同课程条目。
     *
     * @param Collection<int, AppMemberSchedule> $schedules
     * @param int $limit
     * @return Collection<int, AppMemberSchedule>
     */
    private function pickSchedulesByDifferentCourses(Collection $schedules, int $limit): Collection
    {
        if ($limit <= 0 || $schedules->isEmpty()) {
            return collect();
        }

        $picked = collect();
        $pickedIds = [];
        $usedCourseIds = [];

        foreach ($schedules as $schedule) {
            $courseId = (int)($schedule->course_id ?? 0);
            if ($courseId > 0 && isset($usedCourseIds[$courseId])) {
                continue;
            }

            $scheduleId = (int)($schedule->id ?? 0);
            $picked->push($schedule);
            if ($scheduleId > 0) {
                $pickedIds[$scheduleId] = true;
            }
            if ($courseId > 0) {
                $usedCourseIds[$courseId] = true;
            }

            if ($picked->count() >= $limit) {
                return $picked;
            }
        }

        foreach ($schedules as $schedule) {
            if ($picked->count() >= $limit) {
                break;
            }

            $scheduleId = (int)($schedule->id ?? 0);
            if ($scheduleId > 0 && isset($pickedIds[$scheduleId])) {
                continue;
            }

            $picked->push($schedule);
            if ($scheduleId > 0) {
                $pickedIds[$scheduleId] = true;
            }
        }

        return $picked;
    }


    /**
     * 获取学习页分组数据（最近学习 / 待学习 / 已结课）。
     *
     * 课程维度规则：
     * 1. 三组独立，不互斥，允许同一课程跨组重复；
     * 2. 每组内按课程去重，避免同课程多章节重复卡片；
     * 3. 分组判定沿用旧口径（recent/pending/finished），但展示字段统一为课程卡片字段。
     *
     * @param int $memberId
     * @return array<string, array{title:string,list:array<int, array<string, mixed>>}>
     */
    public function getCourseSections(int $memberId): array
    {
        $schedules = $this->queryChapterSchedules($memberId, [], null, true);
        if ($schedules->isEmpty()) {
            return [
                'recentSection'   => ['title' => '最近学习', 'list' => []],
                'pendingSection'  => ['title' => '待学习', 'list' => []],
                'finishedSection' => ['title' => '已结课', 'list' => []],
            ];
        }

        $now = Carbon::now('Asia/Shanghai');
        $courseIds = $schedules->pluck('course_id')->filter()->unique()->values()->all();
        $chapterMetaMap = $this->buildCourseChapterMetaMap($courseIds);
        $groupedSchedules = $this->groupSchedulesByCourse($schedules);

        $recentList = [];
        $pendingList = [];
        $finishedList = [];

        foreach ($groupedSchedules as $courseId => $courseSchedules) {
            $course = $this->resolveCourseFromSchedules($courseSchedules);
            if (!$course) {
                continue;
            }

            $chapterMeta = $chapterMetaMap[$courseId] ?? $this->buildEmptyCourseChapterMeta();
            $representativeSchedule = $this->selectGeneralRepresentativeSchedule($courseSchedules);
            $representativeChapter = $this->resolveRepresentativeChapter($representativeSchedule, $chapterMeta);
            $item = $this->buildSectionItem(
                $course,
                $chapterMeta,
                $representativeSchedule,
                $representativeChapter,
                $now
            );

            if ($courseSchedules->contains(function (AppMemberSchedule $schedule) {
                return (int)$schedule->is_learned === 1;
            })) {
                $item['_sortTs'] = $this->calculateSectionSortTs($courseSchedules, 'recent', $now);
                $recentList[] = $item;
            }

            if ($courseSchedules->contains(function (AppMemberSchedule $schedule) {
                return (int)$schedule->is_learned === 0;
            })) {
                $item['_sortTs'] = $this->calculateSectionSortTs($courseSchedules, 'pending', $now);
                $pendingList[] = $item;
            }

            if ($courseSchedules->contains(function (AppMemberSchedule $schedule) use ($now) {
                return $this->isChapterScheduleFinished($schedule, $now);
            })) {
                $item['_sortTs'] = $this->calculateSectionSortTs($courseSchedules, 'finished', $now);
                $finishedList[] = $item;
            }
        }

        return [
            'recentSection'   => [
                'title' => '最近学习',
                'list'  => $this->sortAndNormalizeSectionItems($recentList, true),
            ],
            'pendingSection'  => [
                'title' => '待学习',
                'list'  => $this->sortAndNormalizeSectionItems($pendingList, false),
            ],
            'finishedSection' => [
                'title' => '已结课',
                'list'  => $this->sortAndNormalizeSectionItems($finishedList, true),
            ],
        ];
    }

    /**
     * 获取筛选后的课程列表（分页，课程维度）。
     *
     * 保持原筛选和分页口径，仅替换卡片字段生成逻辑。
     *
     * @param int $memberId
     * @param int|null $categoryId 课程分类ID
     * @param int|null $payType 课程付费类型
     * @param int $page
     * @param int $pageSize
     * @return array<string, mixed>
     */
    public function getFilteredCourseList(int $memberId, $categoryId, $payType, int $page, int $pageSize): array
    {
        $query = AppMemberCourse::byMember($memberId)
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
            ]);

        // 兼容原筛选行为，避免影响前端面板组合筛选。
        if ($categoryId) {
            $query->whereHas('course', function ($subQuery) use ($categoryId) {
                $subQuery->where('category_id', (int)$categoryId);
            });
        }

        if ($payType) {
            $query->whereHas('course', function ($subQuery) use ($payType) {
                $subQuery->where('pay_type', (int)$payType);
            });
        }

        $query->with(['course:course_id,course_title,cover_image,pay_type'])
            ->orderByRaw('last_learn_time DESC NULLS LAST')
            ->orderBy('enroll_time', 'desc');

        $paginator = $query->paginate($pageSize, ['*'], 'page', $page);
        $courseIds = collect($paginator->items())
            ->map(function (AppMemberCourse $memberCourse) {
                return (int)$memberCourse->course_id;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        $now = Carbon::now('Asia/Shanghai');
        $chapterMetaMap = $this->buildCourseChapterMetaMap($courseIds);
        $scheduleMap = $this->groupSchedulesByCourse(
            $this->queryChapterSchedules($memberId, $courseIds, null, false)
        );

        $list = [];
        foreach ($paginator->items() as $memberCourse) {
            $course = $memberCourse->course;
            if (!$course) {
                continue;
            }

            $courseId = (int)$course->course_id;
            $courseSchedules = $scheduleMap[$courseId] ?? collect();
            $chapterMeta = $chapterMetaMap[$courseId] ?? $this->buildEmptyCourseChapterMeta();
            $representativeSchedule = $this->selectGeneralRepresentativeSchedule($courseSchedules);
            $representativeChapter = $this->resolveRepresentativeChapter($representativeSchedule, $chapterMeta);

            $list[] = $this->buildCourseCardPayload(
                $course,
                $chapterMeta,
                $representativeSchedule,
                $representativeChapter,
                $now,
                self::STATUS_SCENE_GENERAL
            );
        }

        return [
            'list'     => $list,
            'total'    => $paginator->total(),
            'page'     => $paginator->currentPage(),
            'pageSize' => $paginator->perPage(),
        ];
    }

    /**
     * 统一查询章节课表，并按需预加载课程信息。
     *
     * 查询原因：
     * - 三个接口共享同一份课程课表来源，统一入口可避免口径漂移。
     *
     * @param int $memberId
     * @param array<int, int> $courseIds
     * @param string|null $scheduleDate
     * @param bool $withCourse
     * @return Collection<int, AppMemberSchedule>
     */
    private function queryChapterSchedules(
        int     $memberId,
        array   $courseIds = [],
        ?string $scheduleDate = null,
        bool    $withCourse = true
    ): Collection
    {
        $query = AppMemberSchedule::query()
            ->byMember($memberId)
            ->chapterBiz()
            ->select([
                'id',
                'course_id',
                'chapter_id',
                'schedule_date',
                'schedule_time',
                'is_learned',
                'learn_time',
            ])
            ->with([
                'chapter:chapter_id,course_id,chapter_no,chapter_title,chapter_subtitle,chapter_start_time,chapter_end_time',
            ]);

        if ($withCourse) {
            $query->with([
                'course:course_id,course_title,cover_image,pay_type',
            ]);
        }

        if (!empty($courseIds)) {
            $query->whereIn('course_id', $courseIds);
        }

        if ($scheduleDate !== null) {
            $query->where('schedule_date', $scheduleDate);
        }

        return $query
            ->orderBy('schedule_date')
            ->orderBy('schedule_time')
            ->orderBy('id')
            ->get();
    }

    /**
     * 构建课程章节统计与首章信息。
     *
     * 规则：
     * 1. 章节总数来自 app_course_chapter（软删自动排除）；
     * 2. 首章按 sort_order/chapter_no/chapter_id 升序判定；
     * 3. 不额外按 status 过滤，保持与既有课程配置一致。
     *
     * @param array<int, int> $courseIds
     * @return array<int, array{chapterCount:int, firstChapter:?AppCourseChapter, chapterMap:array<int, AppCourseChapter>}>
     */
    private function buildCourseChapterMetaMap(array $courseIds): array
    {
        if (empty($courseIds)) {
            return [];
        }

        $metaMap = [];
        foreach ($courseIds as $courseId) {
            $metaMap[(int)$courseId] = $this->buildEmptyCourseChapterMeta();
        }

        $chapters = AppCourseChapter::query()
            ->whereIn('course_id', $courseIds)
            ->select([
                'chapter_id',
                'course_id',
                'chapter_no',
                'chapter_title',
                'chapter_start_time',
                'chapter_end_time',
                'sort_order',
            ])
            ->orderBy('course_id')
            ->orderBy('sort_order')
            ->orderBy('chapter_no')
            ->orderBy('chapter_id')
            ->get();

        foreach ($chapters as $chapter) {
            $courseId = (int)$chapter->course_id;
            if (!isset($metaMap[$courseId])) {
                $metaMap[$courseId] = $this->buildEmptyCourseChapterMeta();
            }

            $metaMap[$courseId]['chapterCount']++;
            if ($metaMap[$courseId]['firstChapter'] === null) {
                $metaMap[$courseId]['firstChapter'] = $chapter;
            }
            $metaMap[$courseId]['chapterMap'][(int)$chapter->chapter_id] = $chapter;
        }

        return $metaMap;
    }

    /**
     * 空章节统计结构，避免重复写默认值。
     *
     * @return array{chapterCount:int, firstChapter:?AppCourseChapter, chapterMap:array<int, AppCourseChapter>}
     */
    private function buildEmptyCourseChapterMeta(): array
    {
        return [
            'chapterCount' => 0,
            'firstChapter' => null,
            'chapterMap'   => [],
        ];
    }

    /**
     * 将课表按课程分组，并归一化为 int 课程ID键。
     *
     * @param Collection<int, AppMemberSchedule> $schedules
     * @return array<int, Collection<int, AppMemberSchedule>>
     */
    private function groupSchedulesByCourse(Collection $schedules): array
    {
        $grouped = [];
        foreach ($schedules->groupBy('course_id') as $courseId => $courseSchedules) {
            $grouped[(int)$courseId] = $courseSchedules->values();
        }

        return $grouped;
    }

    /**
     * 从课程课表集合中取课程实体。
     *
     * @param Collection<int, AppMemberSchedule> $courseSchedules
     * @return AppCourseBase|null
     */
    private function resolveCourseFromSchedules(Collection $courseSchedules): ?AppCourseBase
    {
        foreach ($courseSchedules as $schedule) {
            if ($schedule->course) {
                return $schedule->course;
            }
        }

        return null;
    }

    /**
     * 选择 today-tasks 的代表章节：当天最早课表。
     *
     * @param Collection<int, AppMemberSchedule> $courseSchedules
     * @return AppMemberSchedule|null
     */
    private function selectTodayRepresentativeSchedule(Collection $courseSchedules): ?AppMemberSchedule
    {
        return $courseSchedules
            ->sort(function (AppMemberSchedule $left, AppMemberSchedule $right) {
                $leftTime = $left->schedule_time ? (string)$left->schedule_time : '23:59:59';
                $rightTime = $right->schedule_time ? (string)$right->schedule_time : '23:59:59';
                if ($leftTime === $rightTime) {
                    return (int)$left->id <=> (int)$right->id;
                }

                return $leftTime <=> $rightTime;
            })
            ->first();
    }

    /**
     * 选择 sections/list 的代表章节。
     *
     * 规则：
     * 1. 优先最早未学章节；
     * 2. 无未学时回退最近已学章节；
     * 3. 都不存在时回退首条课表。
     *
     * @param Collection<int, AppMemberSchedule> $courseSchedules
     * @return AppMemberSchedule|null
     */
    private function selectGeneralRepresentativeSchedule(Collection $courseSchedules): ?AppMemberSchedule
    {
        if ($courseSchedules->isEmpty()) {
            return null;
        }

        $pendingSchedule = $courseSchedules
            ->filter(function (AppMemberSchedule $schedule) {
                return (int)$schedule->is_learned === 0;
            })
            ->sort(function (AppMemberSchedule $left, AppMemberSchedule $right) {
                return $this->compareScheduleAsc($left, $right);
            })
            ->first();

        if ($pendingSchedule) {
            return $pendingSchedule;
        }

        $learnedSchedule = $courseSchedules
            ->filter(function (AppMemberSchedule $schedule) {
                return (int)$schedule->is_learned === 1;
            })
            ->sort(function (AppMemberSchedule $left, AppMemberSchedule $right) {
                $leftLearnTs = $left->learn_time ? (int)$left->learn_time->getTimestamp() : 0;
                $rightLearnTs = $right->learn_time ? (int)$right->learn_time->getTimestamp() : 0;
                if ($leftLearnTs !== $rightLearnTs) {
                    return $rightLearnTs <=> $leftLearnTs;
                }

                return $this->compareScheduleAsc($right, $left);
            })
            ->first();

        if ($learnedSchedule) {
            return $learnedSchedule;
        }

        return $courseSchedules
            ->sort(function (AppMemberSchedule $left, AppMemberSchedule $right) {
                return $this->compareScheduleAsc($left, $right);
            })
            ->first();
    }

    /**
     * 比较两条课表的计划时间（升序）。
     *
     * @param AppMemberSchedule $left
     * @param AppMemberSchedule $right
     * @return int
     */
    private function compareScheduleAsc(AppMemberSchedule $left, AppMemberSchedule $right): int
    {
        $leftAt = $this->resolveScheduleDateTime($left);
        $rightAt = $this->resolveScheduleDateTime($right);

        if ($leftAt && $rightAt) {
            $leftTs = (int)$leftAt->getTimestamp();
            $rightTs = (int)$rightAt->getTimestamp();
            if ($leftTs !== $rightTs) {
                return $leftTs <=> $rightTs;
            }
        } elseif ($leftAt || $rightAt) {
            return $leftAt ? -1 : 1;
        }

        return (int)$left->id <=> (int)$right->id;
    }

    /**
     * 根据课表与课程章节元数据解析代表章节。
     *
     * @param AppMemberSchedule|null $schedule
     * @param array{chapterCount:int, firstChapter:?AppCourseChapter, chapterMap:array<int, AppCourseChapter>} $chapterMeta
     * @return AppCourseChapter|null
     */
    private function resolveRepresentativeChapter(?AppMemberSchedule $schedule, array $chapterMeta): ?AppCourseChapter
    {
        if ($schedule && $schedule->chapter) {
            return $schedule->chapter;
        }

        if ($schedule) {
            $chapterId = (int)($schedule->chapter_id ?? 0);
            if ($chapterId > 0 && isset($chapterMeta['chapterMap'][$chapterId])) {
                return $chapterMeta['chapterMap'][$chapterId];
            }
        }

        return $chapterMeta['firstChapter'] ?? null;
    }

    /**
     * 构建三接口共享的课程卡片字段。
     *
     * 字段来源：
     * - id/title/cover：app_course_base；
     * - overlayText：课程类型文案 + 课程章节总数；
     * - timeText：课程第一章节 chapter_start_time；
     * - statusText：按接口场景计算（today=开课状态，general=学习状态）；
     * - actionText：代表章节学习状态 + 章节标题。
     *
     * @param AppCourseBase $course
     * @param array{chapterCount:int, firstChapter:?AppCourseChapter, chapterMap:array<int, AppCourseChapter>} $chapterMeta
     * @param AppMemberSchedule|null $representativeSchedule
     * @param AppCourseChapter|null $representativeChapter
     * @param Carbon $now
     * @param string $statusScene self::STATUS_SCENE_TODAY|self::STATUS_SCENE_GENERAL
     * @return array<string, mixed>
     */
    private function buildCourseCardPayload(
        AppCourseBase      $course,
        array              $chapterMeta,
        ?AppMemberSchedule $representativeSchedule,
        ?AppCourseChapter  $representativeChapter,
        Carbon             $now,
        string             $statusScene = self::STATUS_SCENE_GENERAL
    ): array
    {
        $firstChapter = $chapterMeta['firstChapter'] ?? null;
        $timeText = $this->buildCourseTimeText($firstChapter);

        return [
            'id'          => (int)($course->course_id ?? 0),
            'title'       => (string)($course->course_title ?? ''),
            'cover'       => (string)($course->cover_image ?? ''),
            'overlayText' => $this->buildCourseOverlayText((int)($course->pay_type ?? 0), (int)($chapterMeta['chapterCount'] ?? 0)),
            'timeText'    => $timeText,
            'statusText'  => $this->resolveCourseStatusText(
                $statusScene,
                $representativeSchedule,
                $representativeChapter,
                $now
            ),
            'actionText'  => $this->buildCourseActionText($representativeSchedule, $representativeChapter, $now),
        ];
    }

    /**
     * 构建学习页封面遮罩文案：课程类型-共N课。
     *
     * @param int $payType
     * @param int $chapterCount
     * @return string
     */
    private function buildCourseOverlayText(int $payType, int $chapterCount): string
    {
        $typeName = $this->resolveCoursePayTypeText($payType);
        return $typeName . '-共' . max($chapterCount, 0) . '课';
    }

    /**
     * 解析课程类型文案，未知值兜底“公开课”。
     *
     * @param int $payType
     * @return string
     */
    private function resolveCoursePayTypeText(int $payType): string
    {
        $config = AppCourseBase::PAY_TYPE_CONFIG[$payType] ?? null;
        if (is_array($config) && !empty($config['typeName'])) {
            return (string)$config['typeName'];
        }

        return '公开课';
    }

    /**
     * 构建 timeText 文案。
     *
     * @param AppCourseChapter|null $firstChapter
     * @return string
     */
    private function buildCourseTimeText(?AppCourseChapter $firstChapter): string
    {
        if (!$firstChapter || !$firstChapter->chapter_start_time) {
            return '';
        }

        return Carbon::make($firstChapter->chapter_start_time)
            ->timezone('Asia/Shanghai')
            ->format('Y.m.d H:i');
    }

    /**
     * 构建 actionText：学习状态 + 章节描述。
     *
     * 格式：{状态}：第N章节：章节标题
     *
     * @param AppMemberSchedule|null $schedule
     * @param AppCourseChapter|null $chapter
     * @param Carbon $now
     * @return string
     */
    private function buildCourseActionText(?AppMemberSchedule $schedule, ?AppCourseChapter $chapter, Carbon $now): string
    {
        $statusText = $this->resolveGeneralCourseStatusText($schedule, $chapter, $now);

        $chapterNo = $chapter ? (int)($chapter->chapter_no ?? 0) : 0;
        $chapterNoText = $chapterNo > 0 ? ('第' . $chapterNo . '章节') : '章节';
        $chapterTitle = $chapter ? trim((string)($chapter->chapter_title ?? '')) : '';
        if ($chapterTitle === '') {
            $chapterTitle = '待安排章节';
        }

        return $statusText . '：' . $chapterNoText . '：' . $chapterTitle;
    }

    /**
     * 按接口场景解析课程卡片 statusText。
     *
     * 场景说明：
     * 1. today：待开课/上课中/已结课（基于章节起止时间）；
     * 2. general：开始学/继续学习/已学完（基于 is_learned + 章节结束时间）。
     *
     * @param string $statusScene
     * @param AppMemberSchedule|null $schedule
     * @param AppCourseChapter|null $chapter
     * @param Carbon $now
     * @return string
     */
    private function resolveCourseStatusText(
        string             $statusScene,
        ?AppMemberSchedule $schedule,
        ?AppCourseChapter  $chapter,
        Carbon             $now
    ): string
    {
        if ($statusScene === self::STATUS_SCENE_TODAY) {
            return $this->resolveTodayCourseStatusText($chapter, $now);
        }

        return $this->resolveGeneralCourseStatusText($schedule, $chapter, $now);
    }

    /**
     * 解析 today-tasks 场景状态文案。
     *
     * 判定规则：
     * 1. now < chapter_start_time => 待开课；
     * 2. chapter_start_time <= now <= chapter_end_time => 上课中；
     * 3. now > chapter_end_time => 已结课；
     * 4. 任一关键时间缺失时兜底待开课，避免返回空状态影响前端展示。
     *
     * @param AppCourseChapter|null $chapter
     * @param Carbon $now
     * @return string
     */
    private function resolveTodayCourseStatusText(?AppCourseChapter $chapter, Carbon $now): string
    {
        if (!$chapter || !$chapter->chapter_start_time || !$chapter->chapter_end_time) {
            return '待开课';
        }

        $chapterStartAt = Carbon::make($chapter->chapter_start_time);
        $chapterEndAt = Carbon::make($chapter->chapter_end_time);
        if (!$chapterStartAt || !$chapterEndAt) {
            // 章节时间异常时按“待开课”兜底，避免前端收到不确定状态。
            return '待开课';
        }

        $chapterStartAt = $chapterStartAt->timezone('Asia/Shanghai');
        $chapterEndAt = $chapterEndAt->timezone('Asia/Shanghai');

        if ($now->lt($chapterStartAt)) {
            return '待开课';
        }

        if ($now->gt($chapterEndAt)) {
            return '已结课';
        }

        return '继续学';
    }

    /**
     * 解析 sections/list 场景学习状态文案（actionText 同步复用此规则）。
     *
     * 优先级：
     * 1. 章节结束时间已过 => 已学完；
     * 2. is_learned=1 => 继续学习；
     * 3. 其他情况 => 开始学。
     *
     * @param AppMemberSchedule|null $schedule
     * @param AppCourseChapter|null $chapter
     * @param Carbon $now
     * @return string
     */
    private function resolveGeneralCourseStatusText(
        ?AppMemberSchedule $schedule,
        ?AppCourseChapter  $chapter,
        Carbon             $now
    ): string
    {
        if ($chapter && $chapter->chapter_end_time) {
            $chapterEndAt = Carbon::make($chapter->chapter_end_time)->timezone('Asia/Shanghai');
            if ($now->gt($chapterEndAt)) {
                return '已学完';
            }
        }

        if ($schedule && (int)$schedule->is_learned === 1) {
            return '继续学习';
        }

        return '开始学';
    }

    /**
     * 组装 sections 场景的课程卡片，并补齐兼容字段。
     *
     * @param AppCourseBase $course
     * @param array{chapterCount:int, firstChapter:?AppCourseChapter, chapterMap:array<int, AppCourseChapter>} $chapterMeta
     * @param AppMemberSchedule|null $representativeSchedule
     * @param AppCourseChapter|null $representativeChapter
     * @param Carbon $now
     * @return array<string, mixed>
     */
    private function buildSectionItem(
        AppCourseBase      $course,
        array              $chapterMeta,
        ?AppMemberSchedule $representativeSchedule,
        ?AppCourseChapter  $representativeChapter,
        Carbon             $now
    ): array
    {
        $item = $this->buildCourseCardPayload(
            $course,
            $chapterMeta,
            $representativeSchedule,
            $representativeChapter,
            $now,
            self::STATUS_SCENE_GENERAL
        );

        return array_merge($item, [
            'scheduleId' => $representativeSchedule ? (int)($representativeSchedule->id ?? 0) : 0,
            'courseId'   => (int)($course->course_id ?? 0),
            'chapterId'  => $representativeSchedule ? (int)($representativeSchedule->chapter_id ?? 0) : 0,
            'liveId'     => 0,
            'bizType'    => 'chapter',
            'bizId'      => $representativeSchedule ? (int)($representativeSchedule->chapter_id ?? 0) : 0,
        ]);
    }

    /**
     * 计算 sections 各分组排序时间戳。
     *
     * @param Collection<int, AppMemberSchedule> $courseSchedules
     * @param string $sectionType recent|pending|finished
     * @param Carbon $now
     * @return int
     */
    private function calculateSectionSortTs(Collection $courseSchedules, string $sectionType, Carbon $now): int
    {
        if ($sectionType === 'recent') {
            return (int)$courseSchedules
                ->filter(function (AppMemberSchedule $schedule) {
                    return (int)$schedule->is_learned === 1 && $schedule->learn_time;
                })
                ->map(function (AppMemberSchedule $schedule) {
                    return (int)$schedule->learn_time->getTimestamp();
                })
                ->max();
        }

        if ($sectionType === 'pending') {
            return (int)$courseSchedules
                ->filter(function (AppMemberSchedule $schedule) {
                    return (int)$schedule->is_learned === 0;
                })
                ->map(function (AppMemberSchedule $schedule) {
                    $scheduleAt = $this->resolveScheduleDateTime($schedule);
                    return $scheduleAt ? (int)$scheduleAt->getTimestamp() : PHP_INT_MAX;
                })
                ->min();
        }

        return (int)$courseSchedules
            ->filter(function (AppMemberSchedule $schedule) use ($now) {
                return $this->isChapterScheduleFinished($schedule, $now);
            })
            ->map(function (AppMemberSchedule $schedule) {
                if (!$schedule->chapter || !$schedule->chapter->chapter_end_time) {
                    return 0;
                }

                return (int)Carbon::make($schedule->chapter->chapter_end_time)
                    ->timezone('Asia/Shanghai')
                    ->getTimestamp();
            })
            ->max();
    }

    /**
     * 判断章节课表是否已结课（仅看 chapter_end_time）。
     *
     * @param AppMemberSchedule $schedule
     * @param Carbon $now
     * @return bool
     */
    private function isChapterScheduleFinished(AppMemberSchedule $schedule, Carbon $now): bool
    {
        if (!$schedule->chapter || !$schedule->chapter->chapter_end_time) {
            return false;
        }

        $chapterEndAt = Carbon::make($schedule->chapter->chapter_end_time)->timezone('Asia/Shanghai');
        return $now->gt($chapterEndAt);
    }

    /**
     * 统一排序并移除内部排序辅助字段。
     *
     * @param array<int, array<string, mixed>> $items
     * @param bool $desc
     * @return array<int, array<string, mixed>>
     */
    private function sortAndNormalizeSectionItems(array $items, bool $desc): array
    {
        usort($items, function (array $left, array $right) use ($desc) {
            $leftSort = (int)($left['_sortTs'] ?? 0);
            $rightSort = (int)($right['_sortTs'] ?? 0);
            if ($leftSort !== $rightSort) {
                return $desc ? ($rightSort <=> $leftSort) : ($leftSort <=> $rightSort);
            }

            $leftId = (int)($left['id'] ?? 0);
            $rightId = (int)($right['id'] ?? 0);
            return $desc ? ($rightId <=> $leftId) : ($leftId <=> $rightId);
        });

        return array_map(function (array $item) {
            unset($item['_sortTs']);
            return $item;
        }, $items);
    }

    /**
     * 解析课表开始时间（用于排序/选代表章节）。
     *
     * @param AppMemberSchedule $schedule
     * @return Carbon|null
     */
    private function resolveScheduleDateTime(AppMemberSchedule $schedule): ?Carbon
    {
        if (!$schedule->schedule_date) {
            return null;
        }

        $dateText = $schedule->schedule_date->format('Y-m-d');
        $timeText = $schedule->schedule_time ? (string)$schedule->schedule_time : '00:00:00';

        return Carbon::parse($dateText . ' ' . $timeText, 'Asia/Shanghai');
    }

    /**
     * 获取课程先导课章节（is_preview=1）。
     *
     * 仅返回在线章节，避免把下架内容暴露给学习中心。
     *
     * @param int $courseId
     * @return Collection<int, AppCourseChapter>
     */
    private function getPreviewChapters(int $courseId): Collection
    {
        return AppCourseChapter::query()
            ->select([
                'chapter_id',
                'course_id',
                'chapter_title',
                'cover_image',
                'sort_order',
                'chapter_no',
            ])
            ->online()
            ->where('course_id', $courseId)
            ->where('is_preview', 1)
            ->with([
                'homeworks' => function ($query) {
                    $query->enabled()
                        ->select([
                            'homework_id',
                            'chapter_id',
                            'course_id',
                            'homework_title',
                            'deadline_days',
                            'sort_order',
                        ])
                        ->orderBy('sort_order')
                        ->orderBy('homework_id');
                },
            ])
            ->orderBy('sort_order')
            ->orderBy('chapter_no')
            ->orderBy('chapter_id')
            ->get();
    }

    /**
     * 生成每日计划 tabs。
     *
     * @param bool $hasPreviewTab
     * @param array<int, string> $dayKeys
     * @param array<string, int> $dateCountMap
     * @param string $todayKey
     * @return array<int, array<string, mixed>>
     */
    private function buildDailyPlanTabs(bool $hasPreviewTab, array $dayKeys, array $dateCountMap, string $todayKey): array
    {
        $tabs = [];

        // TODO 不启用预习
        /*if ($hasPreviewTab) {
            $tabs[] = [
                'key' => 'preview',
                'type' => 'preview',
                'label' => '预习',
                'date' => null,
                'dayNo' => null,
                'hasDot' => true,
                'isToday' => false,
            ];
        }*/

        $dayNo = 1;
        foreach ($dayKeys as $dayKey) {
            $tabs[] = [
                'key'     => $dayKey,
                'type'    => 'day',
                'label'   => '第' . $dayNo . '天',
                'date'    => Carbon::make($dayKey)->format('m.d'),
                'dayNo'   => $dayNo,
                'hasDot'  => (int)($dateCountMap[$dayKey] ?? 0) > 0,
                'isToday' => $dayKey === $todayKey,
            ];

            $dayNo++;
        }

        return $tabs;
    }

    /**
     * 从课表中提取“未学习”日期列表。
     *
     * @param Collection<int, AppMemberSchedule> $schedules
     * @return array<int, string>
     */
    private function extractUnlearnedDayKeys(Collection $schedules): array
    {
        return $schedules
            ->filter(function (AppMemberSchedule $schedule) {
                return (int)$schedule->is_learned === 0;
            })
            ->map(function (AppMemberSchedule $schedule) {
                return $schedule->schedule_date ? $schedule->schedule_date->format('Y-m-d') : '';
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * 解析当前选中的计划 key。
     *
     * 默认优先级：
     * 1. 请求指定且有效；
     * 2. 今日；
     * 3. 最近未学日期（优先未来，其次最近过去）；
     * 4. 首个可用 key。
     *
     * @param string|null $requestedPlanKey
     * @param string $todayKey
     * @param array<int, string> $availablePlanKeys
     * @param array<int, string> $unlearnedDayKeys
     * @return string
     */
    private function resolveSelectedPlanKey(
        ?string $requestedPlanKey,
        string  $todayKey,
        array   $availablePlanKeys,
        array   $unlearnedDayKeys
    ): string
    {
        if (empty($availablePlanKeys)) {
            return '';
        }

        if (!empty($requestedPlanKey) && in_array($requestedPlanKey, $availablePlanKeys, true)) {
            return $requestedPlanKey;
        }

        if (in_array($todayKey, $availablePlanKeys, true)) {
            return $todayKey;
        }

        $futureUnlearned = collect($unlearnedDayKeys)
            ->filter(function (string $dayKey) use ($todayKey) {
                return $dayKey >= $todayKey;
            })
            ->sort()
            ->values();

        if ($futureUnlearned->isNotEmpty()) {
            return (string)$futureUnlearned->first();
        }

        $pastUnlearned = collect($unlearnedDayKeys)
            ->filter(function (string $dayKey) use ($todayKey) {
                return $dayKey < $todayKey;
            })
            ->sortDesc()
            ->values();

        if ($pastUnlearned->isNotEmpty()) {
            return (string)$pastUnlearned->first();
        }

        return (string)$availablePlanKeys[0];
    }

    /**
     * 构建“预习”tab 列表项（先导课）。
     *
     * @param int $memberId
     * @param AppCourseBase $course
     * @param Collection<int, AppCourseChapter> $previewChapters
     * @return array<int, array<string, mixed>>
     */
    private function buildPreviewPlanItems(int $memberId, AppCourseBase $course, Collection $previewChapters): array
    {
        if ($previewChapters->isEmpty()) {
            return [];
        }

        $chapterIds = $previewChapters->pluck('chapter_id')->all();
        $progressMap = $this->getChapterProgressMap($memberId, $chapterIds);
        $chapterVideoUrlMap = $this->getChapterVideoUrlMap($course, $chapterIds);

        $homeworkIds = [];
        foreach ($previewChapters as $chapter) {
            foreach ($chapter->homeworks as $homework) {
                $homeworkIds[] = (int)$homework->homework_id;
            }
        }
        $submittedHomeworkMap = $this->getSubmittedHomeworkMap($memberId, array_unique($homeworkIds));

        $items = [];
        foreach ($previewChapters as $chapter) {
            $progress = $progressMap[(int)$chapter->chapter_id] ?? null;
            $isChapterCompleted = $this->isChapterCompleted(0, $progress);

            $items[] = [
                'itemType'     => 'chapter',
                'chapterId'    => (int)$chapter->chapter_id,
                'chapterTitle' => (string)($chapter->chapter_title ?? ''),
                'scheduleTime' => '',
                'progressText' => $this->buildProgressText($isChapterCompleted, $progress),
                'actionText'   => $isChapterCompleted ? '已学完' : '去学习',
                'actionType'   => $isChapterCompleted ? 'view' : 'learn',
                'coverImage'   => (string)($chapter->cover_image ?: $course->cover_image ?: ''),
                'videoUrl'     => (string)($chapterVideoUrlMap[(int)$chapter->chapter_id] ?? ''),
            ];

            // 先导课不依赖课表日期，不进行过期判定，只展示“去完成/已完成”。
            foreach ($chapter->homeworks as $homework) {
                $homeworkId = (int)$homework->homework_id;
                $isSubmitted = !empty($submittedHomeworkMap[$homeworkId]);

                $items[] = [
                    'itemType'      => 'homework',
                    'homeworkId'    => $homeworkId,
                    'homeworkTitle' => (string)($homework->homework_title ?? ''),
                    'deadlineAt'    => null,
                    'statusText'    => $isSubmitted ? '已完成' : '待完成',
                    'actionText'    => $isSubmitted ? '已完成' : '去完成',
                    'actionType'    => $isSubmitted ? 'view' : 'task',
                ];
            }
        }

        return $items;
    }

    /**
     * 构建某个日期下的章节/作业列表。
     *
     * @param int $memberId
     * @param AppCourseBase $course
     * @param string $date
     * @return array<int, array<string, mixed>>
     */
    private function buildDayPlanItems(int $memberId, AppCourseBase $course, string $date): array
    {
        $schedules = AppMemberSchedule::query()
            ->byMember($memberId)
            ->chapterBiz()
            ->where('course_id', (int)$course->course_id)
            ->where('schedule_date', $date)
            ->select([
                'id',
                'course_id',
                'chapter_id',
                'member_course_id',
                'schedule_date',
                'schedule_time',
                'unlock_time',
                'is_learned',
            ])
            ->with([
                'chapter' => function ($query) {
                    $query->select([
                        'chapter_id',
                        'course_id',
                        'chapter_title',
                        'cover_image',
                        'chapter_no',
                        'sort_order',
                    ])->with([
                        'homeworks' => function ($homeworkQuery) {
                            $homeworkQuery->enabled()
                                ->select([
                                    'homework_id',
                                    'chapter_id',
                                    'course_id',
                                    'homework_title',
                                    'deadline_days',
                                    'sort_order',
                                ])
                                ->orderBy('sort_order')
                                ->orderBy('homework_id');
                        },
                    ]);
                },
            ])
            ->orderBy('schedule_time')
            ->orderBy('id')
            ->get();

        if ($schedules->isEmpty()) {
            return [];
        }

        $chapterIds = $schedules->pluck('chapter_id')->filter()->unique()->values()->all();
        $progressMap = $this->getChapterProgressMap($memberId, $chapterIds);
        $chapterVideoUrlMap = $this->getChapterVideoUrlMap($course, $chapterIds);

        $homeworkIds = [];
        foreach ($schedules as $schedule) {
            if ($schedule->chapter && $schedule->chapter->homeworks) {
                foreach ($schedule->chapter->homeworks as $homework) {
                    $homeworkIds[] = (int)$homework->homework_id;
                }
            }
        }
        $submittedHomeworkMap = $this->getSubmittedHomeworkMap($memberId, array_unique($homeworkIds));

        $items = [];
        foreach ($schedules as $schedule) {
            $chapter = $schedule->chapter;
            $progress = $progressMap[(int)$schedule->chapter_id] ?? null;
            $isChapterCompleted = $this->isChapterCompleted((int)$schedule->is_learned, $progress);

            $items[] = [
                'id'             => $schedule->id,
                'memberCourseId' => $schedule->member_course_id,
                'itemType'       => 'chapter',
                'chapterId'      => (int)$schedule->chapter_id,
                'chapterTitle'   => (string)($chapter->chapter_title ?? ''),
                'scheduleTime'   => $this->formatScheduleTime($schedule->schedule_time),
                'progressText'   => $this->buildProgressText($isChapterCompleted, $progress),
                'actionText'     => $isChapterCompleted ? '已学完' : '去学习',
                'actionType'     => $isChapterCompleted ? 'view' : 'learn',
                'coverImage'     => (string)($chapter->cover_image ?? $course->cover_image ?? ''),
                'videoUrl'       => (string)($chapterVideoUrlMap[(int)$schedule->chapter_id] ?? ''),
                'isUnlocked'     => true
            ];

            if (!$chapter || !$chapter->homeworks || $chapter->homeworks->isEmpty()) {
                continue;
            }

            foreach ($chapter->homeworks as $homework) {
                $homeworkId = (int)$homework->homework_id;
                $isSubmitted = !empty($submittedHomeworkMap[$homeworkId]);

                $deadlineAt = $this->calculateHomeworkDeadlineAt(
                    $schedule->schedule_date,
                    (int)$homework->deadline_days
                );
                $isExpired = !$isSubmitted && $deadlineAt && Carbon::now('Asia/Shanghai')->gt($deadlineAt);

                $statusText = '待完成';
                $actionText = '去完成';
                $actionType = 'task';

                if ($isSubmitted) {
                    $statusText = '已完成';
                    $actionText = '已完成';
                    $actionType = 'view';
                } elseif ($isExpired) {
                    $statusText = '已过期';
                    $actionText = '已过期';
                    $actionType = 'expired';
                }

                $items[] = [
                    'itemType'      => 'homework',
                    'homeworkId'    => $homeworkId,
                    'homeworkTitle' => (string)($homework->homework_title ?? ''),
                    'deadlineAt'    => $deadlineAt ? $deadlineAt->format('Y-m-d H:i:s') : null,
                    'statusText'    => $statusText,
                    'actionText'    => $actionText,
                    'actionType'    => $actionType,
                ];
            }
        }

        return $items;
    }

    /**
     * 批量查询章节进度映射。
     *
     * @param int $memberId
     * @param array<int, int> $chapterIds
     * @return array<int, array{progress:float|int|string,is_completed:int}>
     */
    private function getChapterProgressMap(int $memberId, array $chapterIds): array
    {
        if (empty($chapterIds)) {
            return [];
        }

        return AppMemberSchedule::query()
            ->byMember($memberId)
            ->chapterBiz()
            ->whereIn('chapter_id', $chapterIds)
            ->select(['chapter_id', 'progress', 'is_completed'])
            ->get()
            ->keyBy('chapter_id')
            ->map(function (AppMemberSchedule $progress) {
                return [
                    'progress'     => $progress->progress,
                    'is_completed' => (int)$progress->is_completed,
                ];
            })
            ->toArray();
    }

    /**
     * 批量查询作业是否已提交映射。
     *
     * @param int $memberId
     * @param array<int, int> $homeworkIds
     * @return array<int, bool>
     */
    private function getSubmittedHomeworkMap(int $memberId, array $homeworkIds): array
    {
        if (empty($homeworkIds)) {
            return [];
        }

        $submittedIds = AppMemberHomeworkSubmit::query()
            ->byMember($memberId)
            ->whereIn('homework_id', $homeworkIds)
            ->pluck('homework_id')
            ->map(function ($homeworkId) {
                return (int)$homeworkId;
            })
            ->all();

        return array_fill_keys($submittedIds, true);
    }

    /**
     * 批量查询章节视频地址映射。
     *
     * 规则：
     * 1. 仅录播课（play_type=2）查询视频内容表；
     * 2. 非录播课、空章节集合直接返回空映射；
     * 3. 无视频地址或非字符串值统一归一化为空字符串。
     *
     * @param AppCourseBase $course
     * @param array<int, int> $chapterIds
     * @return array<int, string>
     */
    protected function getChapterVideoUrlMap(AppCourseBase $course, array $chapterIds): array
    {
        if ((int)$course->play_type !== AppCourseBase::PLAY_TYPE_VIDEO || empty($chapterIds)) {
            return [];
        }

        return $this->fetchChapterVideoUrlPairs($chapterIds)
            ->map(function ($videoUrl) {
                return is_string($videoUrl) ? trim($videoUrl) : '';
            })
            ->toArray();
    }

    /**
     * 批量读取章节视频地址键值对（chapter_id => video_url）。
     *
     * @param array<int, int> $chapterIds
     * @return Collection<int, string|null>
     */
    protected function fetchChapterVideoUrlPairs(array $chapterIds): Collection
    {
        return AppChapterContentVideo::query()
            ->whereIn('chapter_id', $chapterIds)
            ->pluck('video_url', 'chapter_id');
    }

    /**
     * 判断章节是否已完成。
     *
     * @param int $scheduleLearned
     * @param array<string, mixed>|null $progress
     * @return bool
     */
    private function isChapterCompleted(int $scheduleLearned, ?array $progress): bool
    {
        if ($scheduleLearned === 1) {
            return true;
        }

        return !empty($progress) && (int)($progress['is_completed'] ?? 0) === 1;
    }

    /**
     * 构建章节进度文案。
     *
     * @param bool $isCompleted
     * @param array<string, mixed>|null $progress
     * @return string
     */
    private function buildProgressText(bool $isCompleted, ?array $progress): string
    {
        if ($isCompleted) {
            return '已学完';
        }

        if (!$progress) {
            return '未学习';
        }

        $pct = (int)($progress['progress'] ?? 0);
        return $pct > 0 ? ('已学' . $pct . '%') : '未学习';
    }

    /**
     * 计算作业截止时间。
     *
     * 规则：
     * 1. deadline_days <= 0 视为不过期；
     * 2. 截止时间按“课表日期 + deadline_days”的当天 23:59:59 计算（东八区）。
     *
     * @param mixed $scheduleDate
     * @param int $deadlineDays
     * @return Carbon|null
     */
    private function calculateHomeworkDeadlineAt($scheduleDate, int $deadlineDays): ?Carbon
    {
        if ($deadlineDays <= 0 || !$scheduleDate) {
            return null;
        }

        $date = $scheduleDate instanceof Carbon
            ? $scheduleDate->copy()
            : Carbon::parse($scheduleDate, 'Asia/Shanghai');

        return $date->timezone('Asia/Shanghai')
            ->addDays($deadlineDays)
            ->endOfDay();
    }

    /**
     * 上报章节播放进度并返回最新快照。
     *
     * 规则：
     * 1. currentPosition 入参使用 hh:mm:ss，服务端转换为秒值后再入库；
     * 2. 同章节重复上报时，learned_duration 仅按历史最大值推进，防止拖动回看导致进度回退；
     * 3. 达到完课阈值后自动流转章节课表 is_learned，并同步课程汇总快照。
     *
     * @param int $memberId
     * @param int $courseId
     * @param int $chapterId
     * @param string $currentPosition
     * @return array{courseId:int,chapterId:int,lastPosition:int,progress:float,isCompleted:int,isLearned:int,learnTime:?string}
     */
    public function reportChapterProgress(
        int    $memberId,
        int    $courseId,
        int    $chapterId,
        string $currentPosition
    ): array
    {
        $currentPositionSeconds = $this->parsePositionTextToSeconds($currentPosition);
        $now = Carbon::now('Asia/Shanghai');

        return DB::transaction(function () use ($memberId, $courseId, $chapterId, $currentPositionSeconds, $now) {
            $context = $this->resolveStudyChapterLearnContext($memberId, $courseId, $chapterId, true);

            /** @var AppMemberCourse $memberCourse */
            $memberCourse = $context['memberCourse'];
            /** @var AppCourseChapter $chapter */
            $chapter = $context['chapter'];
            /** @var AppMemberSchedule $schedule */
            $schedule = $context['schedule'];

            $chapterDuration = max(0, (int)$chapter->duration);
            $storedTotalDuration = max(0, (int)$schedule->total_duration);
            $totalDuration = max($chapterDuration, $storedTotalDuration);
            $normalizedPosition = $this->normalizeProgressPosition($currentPositionSeconds, $totalDuration);
            $learnedDuration = max((int)$schedule->learned_duration, $normalizedPosition);
            $progressPct = $totalDuration > 0
                ? round(min(100, ($learnedDuration / $totalDuration) * 100), 2)
                : 0.0;

            $schedule->total_duration = $totalDuration;
            $schedule->last_position = $normalizedPosition;
            $schedule->learned_duration = $learnedDuration;
            $schedule->progress = $progressPct;
            $schedule->view_count = max(0, (int)$schedule->view_count) + 1;
            if (!$schedule->first_view_time) {
                $schedule->first_view_time = $now->copy();
            }
            $schedule->last_view_time = $now->copy();

            $alreadyCompleted = (int)$schedule->is_completed === 1;
            $completeThreshold = $this->resolveChapterCompleteThreshold($totalDuration, (int)$chapter->min_learn_time);
            $reachCompleteThreshold = $completeThreshold > 0 && $learnedDuration >= $completeThreshold;

            if (!$alreadyCompleted && $reachCompleteThreshold) {
                $schedule->is_completed = 1;
                $schedule->complete_time = $now->copy();
                $alreadyCompleted = true;
            }

            $schedule->save();

            if ($alreadyCompleted) {
                // 自动完课后统一刷新章节课表学习态，保证学习页状态和进度查询保持一致。
                $schedule->is_learned = 1;
                $schedule->learn_time = $now->copy();
                $schedule->save();

                $this->syncMemberCourseLearnSnapshot($memberCourse, $memberId, $courseId, $chapterId, $now);
            } else {
                $this->syncMemberCoursePlaybackSnapshot($memberCourse, $chapterId, $normalizedPosition, $now);
            }

            return $this->buildChapterProgressPayload($courseId, $chapterId, $schedule);
        });
    }

    /**
     * 查询章节播放进度快照。
     *
     * @param int $memberId
     * @param int $courseId
     * @param int $chapterId
     * @return array{courseId:int,chapterId:int,lastPosition:int,progress:float,isCompleted:int,isLearned:int,learnTime:?string}
     */
    public function getChapterProgress(int $memberId, int $courseId, int $chapterId): array
    {
        $context = $this->resolveStudyChapterLearnContext($memberId, $courseId, $chapterId, false);

        /** @var AppMemberSchedule $schedule */
        $schedule = $context['schedule'];

        return $this->buildChapterProgressPayload($courseId, $chapterId, $schedule);
    }

    /**
     * 统一解析课程/章节/课表学习上下文。
     *
     * 校验顺序：
     * 1. 用户课程归属；
     * 2. 课程播放类型（仅录播支持进度）；
     * 3. 章节存在性与归属关系；
     * 4. 用户章节课表存在且已解锁。
     *
     * @param int $memberId
     * @param int $courseId
     * @param int $chapterId
     * @param bool $lockForUpdate
     * @return array{memberCourse:AppMemberCourse,chapter:AppCourseChapter,schedule:AppMemberSchedule}
     */
    private function resolveStudyChapterLearnContext(
        int  $memberId,
        int  $courseId,
        int  $chapterId,
        bool $lockForUpdate
    ): array
    {
        $memberCourseQuery = AppMemberCourse::query()
            ->byMember($memberId)
            ->notExpired()
            ->where('course_id', $courseId);

        if ($lockForUpdate) {
            $memberCourseQuery->lockForUpdate();
        }

        $memberCourse = $memberCourseQuery->first();
        if (!$memberCourse) {
            throw new \DomainException('course_not_owned');
        }

        $course = AppCourseBase::query()
            ->select(['course_id', 'play_type'])
            ->where('course_id', $courseId)
            ->first();

        if (!$course) {
            throw new \DomainException('course_not_found');
        }

        if ((int)$course->play_type !== AppCourseBase::PLAY_TYPE_VIDEO) {
            throw new \DomainException('non_video_course');
        }

        $chapter = AppCourseChapter::query()
            ->select(['chapter_id', 'course_id', 'duration', 'min_learn_time'])
            ->where('course_id', $courseId)
            ->where('chapter_id', $chapterId)
            ->first();

        if (!$chapter) {
            throw new \DomainException('chapter_not_found');
        }

        $scheduleQuery = AppMemberSchedule::query()
            ->byMember($memberId)
            ->chapterBiz()
            ->where('course_id', $courseId)
            ->where('chapter_id', $chapterId);

        if ($lockForUpdate) {
            $scheduleQuery->lockForUpdate();
        }

        $schedule = $scheduleQuery->first();
        if (!$schedule) {
            throw new \DomainException('schedule_not_found');
        }

        if ((int)$schedule->is_unlocked !== 1) {
            throw new \DomainException('schedule_not_unlocked');
        }

        return [
            'memberCourse' => $memberCourse,
            'chapter'      => $chapter,
            'schedule'     => $schedule,
        ];
    }

    /**
     * 解析播放器上报的 hh:mm:ss 文本为秒值。
     *
     * @param string $positionText
     * @return int
     */
    private function parsePositionTextToSeconds(string $positionText): int
    {
        $value = trim($positionText);
        if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $value)) {
            throw new \DomainException('position_format_invalid');
        }

        $parts = array_map('intval', explode(':', $value));
        $hours = (int)($parts[0] ?? 0);
        $minutes = (int)($parts[1] ?? 0);
        $seconds = (int)($parts[2] ?? 0);

        if ($minutes >= 60 || $seconds >= 60) {
            throw new \DomainException('position_format_invalid');
        }

        return ($hours * 3600) + ($minutes * 60) + $seconds;
    }

    /**
     * 归一化播放位置。
     *
     * @param int $position
     * @param int $totalDuration
     * @return int
     */
    private function normalizeProgressPosition(int $position, int $totalDuration): int
    {
        $normalized = max(0, $position);
        if ($totalDuration > 0) {
            return min($normalized, $totalDuration);
        }

        return $normalized;
    }

    /**
     * 解析章节自动完课阈值（秒）。
     *
     * 规则：
     * 1. min_learn_time > 0 时优先使用；
     * 2. 若同时有 total_duration，则阈值上限裁剪到 total_duration，避免出现无法达标；
     * 3. min_learn_time 无效时回退 total_duration 的 90%。
     *
     * @param int $totalDuration
     * @param int $minLearnTime
     * @return int
     */
    private function resolveChapterCompleteThreshold(int $totalDuration, int $minLearnTime): int
    {
        $duration = max(0, $totalDuration);
        $minLearn = max(0, $minLearnTime);

        if ($minLearn > 0) {
            return $duration > 0 ? min($minLearn, $duration) : $minLearn;
        }

        if ($duration <= 0) {
            return 0;
        }

        return (int)ceil($duration * 0.9);
    }

    /**
     * 同步用户课程的播放锚点快照。
     *
     * @param AppMemberCourse $memberCourse
     * @param int $chapterId
     * @param int $position
     * @param Carbon $now
     * @return void
     */
    private function syncMemberCoursePlaybackSnapshot(
        AppMemberCourse $memberCourse,
        int             $chapterId,
        int             $position,
        Carbon          $now
    ): void
    {
        $memberCourse->last_chapter_id = $chapterId;
        $memberCourse->last_position = $position;
        $memberCourse->last_learn_time = $now->copy();
        $memberCourse->save();
    }

    /**
     * 构建章节进度响应快照。
     *
     * @param int $courseId
     * @param int $chapterId
     * @param AppMemberSchedule $schedule
     * @return array{courseId:int,chapterId:int,lastPosition:int,progress:float,isCompleted:int,isLearned:int,learnTime:?string}
     */
    private function buildChapterProgressPayload(
        int               $courseId,
        int               $chapterId,
        AppMemberSchedule $schedule
    ): array
    {
        return [
            'courseId'     => $courseId,
            'chapterId'    => $chapterId,
            'lastPosition' => (int)$schedule->last_position,
            'progress'     => round((float)$schedule->progress, 2),
            'isCompleted'  => (int)$schedule->is_completed,
            'isLearned'    => (int)$schedule->is_learned,
            'learnTime'    => $schedule->learn_time
                ? $schedule->learn_time->format('Y-m-d H:i:s')
                : null,
        ];
    }

    /**
     * 统一格式化课表时间，输出 HH:mm。
     *
     * @param string|null $scheduleTime
     * @return string
     */
    private function formatScheduleTime(?string $scheduleTime): string
    {
        if (empty($scheduleTime)) {
            return '';
        }

        return strlen($scheduleTime) >= 5 ? substr($scheduleTime, 0, 5) : $scheduleTime;
    }
}
