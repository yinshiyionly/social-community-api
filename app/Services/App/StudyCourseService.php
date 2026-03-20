<?php

namespace App\Services\App;

use App\Models\App\AppCourseChapter;
use App\Models\App\AppCourseBase;
use App\Models\App\AppCourseCategory;
use App\Models\App\AppChapterContentVideo;
use App\Models\App\AppMemberChapterProgress;
use App\Models\App\AppMemberCourse;
use App\Models\App\AppMemberHomeworkSubmit;
use App\Models\App\AppMemberSchedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * 学习页（课程Tab）服务。
 *
 * 职责：
 * 1. 提供学习中心课程筛选、分组与列表查询；
 * 2. 聚合学习中心课程详情（课程头部 + 每日计划 tabs + 当日章节/作业项）；
 * 3. 统一章节进度与作业状态文案，避免控制器重复拼装业务规则。
 */
class StudyCourseService
{
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
            'course' => [
                'courseId' => $courseId,
                'courseTitle' => (string)($course->course_title ?? ''),
                'lecturerName' => (string)($course->teacher_name ?? ''),
                'classTeacherName' => (string)($course->class_teacher_name ?? ''),
                'classTeacherQr' => (string)($course->class_teacher_qr ?? ''),
            ],
            'dailyPlan' => [
                'selectedPlanKey' => $selectedPlanKey,
                'todayPlanKey' => in_array($todayKey, $dayKeys, true) ? $todayKey : '',
                'tabs' => $this->buildDailyPlanTabs(
                    $previewChapters->isNotEmpty(),
                    $dayKeys,
                    $dateCountMap,
                    $todayKey
                ),
                'items' => $items,
            ],
        ];
    }

    /**
     * 获取今日学习任务（章节 + 直播）。
     *
     * 规则：
     * 1. 章节任务沿用课表原口径；
     * 2. 直播任务使用 biz_type=2，标题取直播间标题；
     * 3. 直播间被删除或不可读时跳过该条课表，避免返回空卡片。
     *
     * @param int $memberId
     * @return array<int, array<string, mixed>>
     */
    public function getTodayTasks(int $memberId): array
    {
        $todaySchedules = AppMemberSchedule::byMember($memberId)
            ->today()
            ->select([
                'id',
                'biz_type',
                'room_id',
                'course_id',
                'chapter_id',
                'schedule_date',
                'schedule_time',
                'is_learned',
            ])
            ->with([
                'chapter:chapter_id,chapter_title,chapter_subtitle',
                'course:course_id,course_title,play_type',
                'liveRoom:room_id,room_title,room_cover',
            ])
            ->orderBy('schedule_time')
            ->orderBy('id')
            ->get();

        $todayTasks = [];
        foreach ($todaySchedules as $schedule) {
            if ($this->shouldSkipInvalidLiveSchedule($schedule)) {
                continue;
            }

            $isLiveSchedule = $this->isLiveSchedule($schedule);
            $chapter = $schedule->chapter;
            $liveRoom = $schedule->liveRoom;

            $statusText = '待学习';
            if ((int)$schedule->is_learned === 1) {
                $statusText = '已学完';
            } elseif ($schedule->schedule_time) {
                $statusText = '待开课';
            }

            $todayTasks[] = [
                'id' => (int)($schedule->id ?? 0),
                'courseId' => (int)($schedule->course_id ?? 0),
                'time' => $schedule->schedule_time ? substr((string)$schedule->schedule_time, 0, 5) : '',
                'title' => $isLiveSchedule
                    ? ($liveRoom ? (string)($liveRoom->room_title ?? '') : '')
                    : ($chapter ? (string)($chapter->chapter_title ?? '') : ''),
                'subtitle' => $isLiveSchedule
                    ? ''
                    : ($chapter ? (string)($chapter->chapter_subtitle ?? '') : ''),
                'statusText' => $statusText,
                // 兼容旧字段，同时补充业务定位字段供前端渐进切换。
                'bizType' => $isLiveSchedule ? 'live' : 'chapter',
                'bizId' => $isLiveSchedule
                    ? (int)($schedule->room_id ?? 0)
                    : (int)($schedule->chapter_id ?? 0),
                'liveId' => $isLiveSchedule ? (int)($schedule->room_id ?? 0) : 0,
                'chapterId' => $isLiveSchedule ? 0 : (int)($schedule->chapter_id ?? 0),
            ];
        }

        return $todayTasks;
    }

    /**
     * 获取学习页分组数据（最近学习 / 待学习 / 已结课）。
     *
     * 业务口径：
     * 1. 最近学习：is_learned=1，按 learn_time 倒序；
     * 2. 待学习：is_learned=0，按 schedule_date/schedule_time 正序；
     * 3. 已结课：章节看 chapter_end_time，直播看 scheduled_end_time（含兜底）。
     *
     * 注意：
     * - 三组独立，不互斥，允许重复；
     * - 直播课表失效数据默认跳过。
     *
     * @param int $memberId
     * @return array<string, array{title:string,list:array<int, array<string, mixed>>}>
     */
    public function getCourseSections(int $memberId): array
    {
        $schedules = AppMemberSchedule::byMember($memberId)
            ->select([
                'id',
                'biz_type',
                'room_id',
                'course_id',
                'chapter_id',
                'schedule_date',
                'schedule_time',
                'is_learned',
                'learn_time',
            ])
            ->with([
                'chapter:chapter_id,course_id,chapter_title,cover_image,chapter_end_time',
                'course:course_id,course_title,cover_image,play_type,pay_type',
                'liveRoom:room_id,room_title,room_cover,scheduled_start_time,scheduled_end_time',
            ])
            ->get();

        $now = Carbon::now('Asia/Shanghai');

        $recentList = [];
        $pendingList = [];
        $finishedList = [];

        foreach ($schedules as $schedule) {
            if ($this->shouldSkipInvalidLiveSchedule($schedule)) {
                continue;
            }

            if ((int)$schedule->is_learned === 1) {
                $recentList[] = $this->formatScheduleSectionItem($schedule, 'recent');
            }

            if ((int)$schedule->is_learned === 0) {
                $pendingList[] = $this->formatScheduleSectionItem($schedule, 'pending');
            }

            if ($this->isScheduleFinished($schedule, $now)) {
                $finishedList[] = $this->formatScheduleSectionItem($schedule, 'finished');
            }
        }

        $recentList = $this->sortAndNormalizeSectionItems($recentList, true);
        $pendingList = $this->sortAndNormalizeSectionItems($pendingList, false);
        $finishedList = $this->sortAndNormalizeSectionItems($finishedList, true);

        return [
            'recentSection' => [
                'title' => '最近学习',
                'list' => $recentList,
            ],
            'pendingSection' => [
                'title' => '待学习',
                'list' => $pendingList,
            ],
            'finishedSection' => [
                'title' => '已结课',
                'list' => $finishedList,
            ],
        ];
    }

    /**
     * 判断是否为直播课表。
     *
     * @param AppMemberSchedule $schedule
     * @return bool
     */
    private function isLiveSchedule(AppMemberSchedule $schedule): bool
    {
        return (int)$schedule->biz_type === AppMemberSchedule::BIZ_TYPE_LIVE;
    }

    /**
     * 过滤无效直播课表（直播间已删除或不可读）。
     *
     * @param AppMemberSchedule $schedule
     * @return bool
     */
    private function shouldSkipInvalidLiveSchedule(AppMemberSchedule $schedule): bool
    {
        return $this->isLiveSchedule($schedule) && !$schedule->liveRoom;
    }

    /**
     * 格式化学习页分组卡片。
     *
     * @param AppMemberSchedule $schedule
     * @param string $sectionType recent|pending|finished
     * @return array<string, mixed>
     */
    private function formatScheduleSectionItem(AppMemberSchedule $schedule, string $sectionType): array
    {
        $isLiveSchedule = $this->isLiveSchedule($schedule);
        $chapter = $schedule->chapter;
        $course = $schedule->course;
        $liveRoom = $schedule->liveRoom;

        $chapterTitle = $chapter ? (string)($chapter->chapter_title ?? '') : '';
        if ($chapterTitle === '' && $course) {
            $chapterTitle = (string)($course->course_title ?? '');
        }

        $chapterCover = $chapter ? (string)($chapter->cover_image ?? '') : '';
        if ($chapterCover === '' && $course) {
            $chapterCover = (string)($course->cover_image ?? '');
        }

        $actionText = '去学习';
        if ($sectionType === 'finished') {
            $actionText = '已结课';
        } elseif ((int)$schedule->is_learned === 1) {
            $actionText = '继续学';
        }

        $sortTs = 0;
        if ($sectionType === 'recent') {
            $sortTs = $schedule->learn_time ? (int)$schedule->learn_time->getTimestamp() : 0;
        } elseif ($sectionType === 'pending') {
            $scheduleAt = $this->resolveScheduleDateTime($schedule);
            $sortTs = $scheduleAt ? (int)$scheduleAt->getTimestamp() : PHP_INT_MAX;
        } else {
            $endAt = $this->resolveScheduleEndAt($schedule);
            $sortTs = $endAt ? (int)$endAt->getTimestamp() : 0;
        }

        return [
            'id' => $isLiveSchedule
                ? (int)($schedule->room_id ?? 0)
                : (int)($schedule->course_id ?? $schedule->chapter_id ?? $schedule->id),
            'title' => $isLiveSchedule
                ? ($liveRoom ? (string)($liveRoom->room_title ?? '') : '')
                : $chapterTitle,
            'cover' => $isLiveSchedule
                ? ($liveRoom ? (string)($liveRoom->room_cover ?? '') : '')
                : $chapterCover,
            'overlayText' => $this->buildScheduleOverlayText($schedule),
            'actionText' => $actionText,
            // 兼容旧字段基础上补充明确业务主键，便于前端平滑切换。
            'scheduleId' => (int)($schedule->id ?? 0),
            'courseId' => (int)($schedule->course_id ?? 0),
            'chapterId' => $isLiveSchedule ? 0 : (int)($schedule->chapter_id ?? 0),
            'liveId' => $isLiveSchedule ? (int)($schedule->room_id ?? 0) : 0,
            'bizType' => $isLiveSchedule ? 'live' : 'chapter',
            'bizId' => $isLiveSchedule
                ? (int)($schedule->room_id ?? 0)
                : (int)($schedule->chapter_id ?? 0),
            '_sortTs' => $sortTs,
        ];
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

            if ($leftSort === $rightSort) {
                $leftId = (int)($left['scheduleId'] ?? 0);
                $rightId = (int)($right['scheduleId'] ?? 0);
                return $desc ? ($rightId <=> $leftId) : ($leftId <=> $rightId);
            }

            return $desc ? ($rightSort <=> $leftSort) : ($leftSort <=> $rightSort);
        });

        return array_map(function (array $item) {
            unset($item['_sortTs']);
            return $item;
        }, $items);
    }

    /**
     * 判断课表是否已结课。
     *
     * @param AppMemberSchedule $schedule
     * @param Carbon $now
     * @return bool
     */
    private function isScheduleFinished(AppMemberSchedule $schedule, Carbon $now): bool
    {
        $endAt = $this->resolveScheduleEndAt($schedule);
        if (!$endAt) {
            return false;
        }

        return $now->gt($endAt);
    }

    /**
     * 解析课表结束时间（章节/直播双口径）。
     *
     * @param AppMemberSchedule $schedule
     * @return Carbon|null
     */
    private function resolveScheduleEndAt(AppMemberSchedule $schedule): ?Carbon
    {
        if ($this->isLiveSchedule($schedule)) {
            return $this->resolveLiveScheduleEndAt($schedule);
        }

        if (!$schedule->chapter || !$schedule->chapter->chapter_end_time) {
            return null;
        }

        return Carbon::make($schedule->chapter->chapter_end_time)->timezone('Asia/Shanghai');
    }

    /**
     * 解析直播课表结束时间。
     *
     * 兜底优先级：
     * 1. scheduled_end_time；
     * 2. scheduled_start_time；
     * 3. schedule_date 当天 23:59:59。
     *
     * @param AppMemberSchedule $schedule
     * @return Carbon|null
     */
    private function resolveLiveScheduleEndAt(AppMemberSchedule $schedule): ?Carbon
    {
        $liveRoom = $schedule->liveRoom;
        if (!$liveRoom) {
            return null;
        }

        if ($liveRoom->scheduled_end_time) {
            return Carbon::make($liveRoom->scheduled_end_time)->timezone('Asia/Shanghai');
        }

        if ($liveRoom->scheduled_start_time) {
            return Carbon::make($liveRoom->scheduled_start_time)->timezone('Asia/Shanghai');
        }

        if (!$schedule->schedule_date) {
            return null;
        }

        return Carbon::make($schedule->schedule_date)->timezone('Asia/Shanghai')->endOfDay();
    }

    /**
     * 解析课表开始时间（用于排序）。
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
     * 构建封面 overlay 时间文案。
     *
     * @param AppMemberSchedule $schedule
     * @return string
     */
    private function buildScheduleOverlayText(AppMemberSchedule $schedule): string
    {
        if (!$schedule->schedule_date) {
            return '';
        }

        $dateText = $schedule->schedule_date->format('n月j日');
        $timeText = $schedule->schedule_time ? substr((string)$schedule->schedule_time, 0, 5) : '';
        if ($timeText === '') {
            return $dateText;
        }

        return $dateText . ' ' . $timeText;
    }

    /**
     * 获取筛选后的课程列表（分页）
     *
     * @param int $memberId
     * @param int|null $categoryId 课程分类ID
     * @param int|null $payType 课程付费类型
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getFilteredCourseList(int $memberId, $categoryId, $payType, int $page, int $pageSize): array
    {
        $query = AppMemberCourse::byMember($memberId)
            ->notExpired()
            ->select([
                'id', 'course_id', 'progress', 'learned_chapters', 'total_chapters',
                'is_completed', 'last_learn_time', 'enroll_time',
            ]);

        // 按课程分类筛选
        if ($categoryId) {
            $query->whereHas('course', function ($q) use ($categoryId) {
                $q->where('category_id', (int)$categoryId);
            });
        }

        // 按付费类型筛选
        if ($payType) {
            $query->whereHas('course', function ($q) use ($payType) {
                $q->where('pay_type', (int)$payType);
            });
        }

        $query->with(['course:course_id,course_title,cover_image,play_type,pay_type'])
            ->orderByRaw('last_learn_time DESC NULLS LAST')
            ->orderBy('enroll_time', 'desc');

        $paginator = $query->paginate($pageSize, ['*'], 'page', $page);

        // 预加载下一节未学课表（用于直播课 statusText）
        $memberCourseIds = [];
        foreach ($paginator->items() as $mc) {
            $memberCourseIds[] = $mc->id;
        }

        $nextSchedules = [];
        if (!empty($memberCourseIds)) {
            // 列表页仍按章节课表取“下一节”，避免直播预约数据干扰课程进度展示。
            $schedules = AppMemberSchedule::whereIn('member_course_id', $memberCourseIds)
                ->chapterBiz()
                ->where('is_learned', 0)
                ->where('schedule_date', '>=', date('Y-m-d'))
                ->orderBy('schedule_date')
                ->orderBy('schedule_time')
                ->get()
                ->groupBy('member_course_id');

            foreach ($schedules as $mcId => $group) {
                $nextSchedules[$mcId] = $group->first();
            }
        }

        $list = [];
        foreach ($paginator->items() as $mc) {
            $course = $mc->course;
            if (!$course) {
                continue;
            }
            $nextSchedule = isset($nextSchedules[$mc->id]) ? $nextSchedules[$mc->id] : null;
            $list[] = $this->formatCourseListItem($mc, $course, $nextSchedule);
        }

        return [
            'list' => $list,
            'total' => $paginator->total(),
            'page' => $paginator->currentPage(),
            'pageSize' => $paginator->perPage(),
        ];
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
                'key' => $dayKey,
                'type' => 'day',
                'label' => '第' . $dayNo . '天',
                'date' => Carbon::make($dayKey)->format('m.d'),
                'dayNo' => $dayNo,
                'hasDot' => (int)($dateCountMap[$dayKey] ?? 0) > 0,
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
        string $todayKey,
        array $availablePlanKeys,
        array $unlearnedDayKeys
    ): string {
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
                'itemType' => 'chapter',
                'chapterId' => (int)$chapter->chapter_id,
                'chapterTitle' => (string)($chapter->chapter_title ?? ''),
                'scheduleTime' => '',
                'progressText' => $this->buildProgressText($isChapterCompleted, $progress),
                'actionText' => $isChapterCompleted ? '已学完' : '去学习',
                'actionType' => $isChapterCompleted ? 'view' : 'learn',
                'coverImage' => (string)($chapter->cover_image ?: $course->cover_image ?: ''),
                'videoUrl' => (string)($chapterVideoUrlMap[(int)$chapter->chapter_id] ?? ''),
            ];

            // 先导课不依赖课表日期，不进行过期判定，只展示“去完成/已完成”。
            foreach ($chapter->homeworks as $homework) {
                $homeworkId = (int)$homework->homework_id;
                $isSubmitted = !empty($submittedHomeworkMap[$homeworkId]);

                $items[] = [
                    'itemType' => 'homework',
                    'homeworkId' => $homeworkId,
                    'homeworkTitle' => (string)($homework->homework_title ?? ''),
                    'deadlineAt' => null,
                    'statusText' => $isSubmitted ? '已完成' : '待完成',
                    'actionText' => $isSubmitted ? '已完成' : '去完成',
                    'actionType' => $isSubmitted ? 'view' : 'task',
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
                'id' => $schedule->id,
                'memberCourseId' => $schedule->member_course_id,
                'itemType' => 'chapter',
                'chapterId' => (int)$schedule->chapter_id,
                'chapterTitle' => (string)($chapter->chapter_title ?? ''),
                'scheduleTime' => $this->formatScheduleTime($schedule->schedule_time),
                'progressText' => $this->buildProgressText($isChapterCompleted, $progress),
                'actionText' => $isChapterCompleted ? '已学完' : '去学习',
                'actionType' => $isChapterCompleted ? 'view' : 'learn',
                'coverImage' => (string)($chapter->cover_image ?? $course->cover_image ?? ''),
                'videoUrl' => (string)($chapterVideoUrlMap[(int)$schedule->chapter_id] ?? ''),
                'isUnlocked' => true
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
                    'itemType' => 'homework',
                    'homeworkId' => $homeworkId,
                    'homeworkTitle' => (string)($homework->homework_title ?? ''),
                    'deadlineAt' => $deadlineAt ? $deadlineAt->format('Y-m-d H:i:s') : null,
                    'statusText' => $statusText,
                    'actionText' => $actionText,
                    'actionType' => $actionType,
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

        return AppMemberChapterProgress::query()
            ->byMember($memberId)
            ->whereIn('chapter_id', $chapterIds)
            ->select(['chapter_id', 'progress', 'is_completed'])
            ->get()
            ->keyBy('chapter_id')
            ->map(function (AppMemberChapterProgress $progress) {
                return [
                    'progress' => $progress->progress,
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


    /**
     * 格式化总览课程项
     *
     * @param AppMemberCourse $mc
     * @param AppCourseBase $course
     * @param AppMemberSchedule|null $nextSchedule 下一节未学课表
     * @return array
     */
    private function formatCourseOverviewItem(AppMemberCourse $mc, AppCourseBase $course, $nextSchedule = null): array
    {
        // overlayText：优先显示下一节开课时间，否则显示付费类型
        $overlayText = '';
        if ($nextSchedule && $nextSchedule->schedule_date) {
            $dateStr = $nextSchedule->schedule_date->format('n月j日');
            $timeStr = $nextSchedule->schedule_time ? substr($nextSchedule->schedule_time, 0, 5) : '';
            $overlayText = $timeStr ? ($dateStr . ' ' . $timeStr) : $dateStr;
        } else {
            $payTypeConfig = isset(AppCourseBase::PAY_TYPE_CONFIG[$course->pay_type])
                ? AppCourseBase::PAY_TYPE_CONFIG[$course->pay_type]
                : null;
            if ($payTypeConfig) {
                $overlayText = $payTypeConfig['typeName'];
            }
        }

        $actionText = '去学习';
        if ($mc->is_completed) {
            $actionText = '已结课';
        } elseif ($mc->last_learn_time) {
            $actionText = '继续学';
        }

        return [
            'id' => $mc->course_id,
            'title' => $course->course_title,
            'cover' => $course->cover_image,
            'overlayText' => $overlayText,
            'actionText' => $actionText,
        ];
    }

    /**
     * 格式化筛选列表课程项
     *
     * @param AppMemberCourse $mc
     * @param AppCourseBase $course
     * @param AppMemberSchedule|null $nextSchedule 下一节未学课表
     * @return array
     */
    private function formatCourseListItem(AppMemberCourse $mc, AppCourseBase $course, $nextSchedule = null): array
    {
        $overlayText = '';
        $payTypeConfig = isset(AppCourseBase::PAY_TYPE_CONFIG[$course->pay_type])
            ? AppCourseBase::PAY_TYPE_CONFIG[$course->pay_type]
            : null;
        if ($payTypeConfig) {
            $overlayText = $payTypeConfig['typeName'];
        }

        // 时间文案（直播课显示下一节开课时间）
        $timeText = '';
        if ($nextSchedule && $nextSchedule->schedule_date) {
            $dateStr = $nextSchedule->schedule_date->format('Y.m.d');
            $timeStr = $nextSchedule->schedule_time ? substr($nextSchedule->schedule_time, 0, 5) : '';
            $timeText = $timeStr ? ($dateStr . ' ' . $timeStr) : $dateStr;
        }

        // 状态文案
        $statusText = '';
        if ($mc->is_completed) {
            $statusText = '已结课';
        } elseif ($nextSchedule && $nextSchedule->schedule_date) {
            $dateStr = $nextSchedule->schedule_date->format('Y.m.d');
            $timeStr = $nextSchedule->schedule_time ? substr($nextSchedule->schedule_time, 0, 5) : '';
            $statusText = $timeStr ? ($dateStr . ' ' . $timeStr . ' 开课') : ($dateStr . ' 开课');
        } elseif ($mc->progress > 0) {
            $statusText = '已学' . (int)$mc->progress . '%';
        } else {
            $statusText = '未学习';
        }

        $actionText = '去学习';
        if ($mc->is_completed) {
            $actionText = '已结课';
        } elseif ($mc->last_learn_time) {
            $actionText = '继续学';
        }

        return [
            'id' => $mc->course_id,
            'title' => $course->course_title,
            'cover' => $course->cover_image,
            'overlayText' => $overlayText,
            'timeText' => $timeText,
            'statusText' => $statusText,
            'actionText' => $actionText,
        ];
    }

}
