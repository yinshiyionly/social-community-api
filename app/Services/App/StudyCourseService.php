<?php

namespace App\Services\App;

use App\Models\App\AppCourseChapter;
use App\Models\App\AppCourseBase;
use App\Models\App\AppCourseCategory;
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
     * 获取今日学习任务。
     *
     * @param int $memberId
     * @return array
     */
    public function getTodayTasks(int $memberId): array
    {
        $todaySchedules = AppMemberSchedule::byMember($memberId)
            ->today()
            ->select(['id', 'course_id', 'chapter_id', 'schedule_time', 'is_learned'])
            ->with([
                'chapter:chapter_id,chapter_title,chapter_subtitle',
                'course:course_id,course_title,play_type',
            ])
            ->orderBy('schedule_time')
            ->get();

        $todayTasks = [];
        foreach ($todaySchedules as $schedule) {
            $chapter = $schedule->chapter;

            $statusText = '待学习';
            if ($schedule->is_learned) {
                $statusText = '已学完';
            } elseif ($schedule->schedule_time) {
                $statusText = '待开课';
            }

            $todayTasks[] = [
                'id' => $schedule->id,
                'time' => $schedule->schedule_time ? substr($schedule->schedule_time, 0, 5) : '',
                'title' => $chapter ? $chapter->chapter_title : '',
                'subtitle' => $chapter ? $chapter->chapter_subtitle : '',
                'statusText' => $statusText,
            ];
        }

        return $todayTasks;
    }

    /**
     * 获取学习页分组数据（最近学习 / 待学习 / 已结课）
     *
     * @param int $memberId
     * @return array
     */
    public function getCourseSections(int $memberId): array
    {
        $memberCourses = AppMemberCourse::byMember($memberId)
            ->notExpired()
            ->select([
                'id', 'course_id', 'progress', 'learned_chapters', 'total_chapters',
                'is_completed', 'last_learn_time', 'last_chapter_id', 'enroll_time',
            ])
            ->with(['course:course_id,course_title,cover_image,play_type,pay_type'])
            ->orderByRaw('last_learn_time DESC NULLS LAST')
            ->orderBy('enroll_time', 'desc')
            ->get();

        // 预加载下一节未学课表（用于 overlayText 显示开课时间）
        $memberCourseIds = [];
        foreach ($memberCourses as $mc) {
            $memberCourseIds[] = $mc->id;
        }

        $nextSchedules = [];
        if (!empty($memberCourseIds)) {
            $schedules = AppMemberSchedule::whereIn('member_course_id', $memberCourseIds)
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

        $recentList = [];
        $pendingList = [];
        $finishedList = [];

        foreach ($memberCourses as $mc) {
            $course = $mc->course;
            if (!$course) {
                continue;
            }

            $nextSchedule = isset($nextSchedules[$mc->id]) ? $nextSchedules[$mc->id] : null;
            $item = $this->formatCourseOverviewItem($mc, $course, $nextSchedule);

            if ($mc->is_completed) {
                $finishedList[] = $item;
            } elseif ($mc->last_learn_time) {
                $recentList[] = $item;
            } else {
                $pendingList[] = $item;
            }
        }

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
            $schedules = AppMemberSchedule::whereIn('member_course_id', $memberCourseIds)
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

        if ($hasPreviewTab) {
            $tabs[] = [
                'key' => 'preview',
                'type' => 'preview',
                'label' => '预习',
                'date' => null,
                'dayNo' => null,
                'hasDot' => true,
                'isToday' => false,
            ];
        }

        $dayNo = 1;
        foreach ($dayKeys as $dayKey) {
            $tabs[] = [
                'key' => $dayKey,
                'type' => 'day',
                'label' => '第' . $dayNo . '天',
                'date' => $dayKey,
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
            ->where('course_id', (int)$course->course_id)
            ->where('schedule_date', $date)
            ->select([
                'id',
                'course_id',
                'chapter_id',
                'schedule_date',
                'schedule_time',
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
                'itemType' => 'chapter',
                'chapterId' => (int)$schedule->chapter_id,
                'chapterTitle' => (string)($chapter->chapter_title ?? ''),
                'scheduleTime' => $this->formatScheduleTime($schedule->schedule_time),
                'progressText' => $this->buildProgressText($isChapterCompleted, $progress),
                'actionText' => $isChapterCompleted ? '已学完' : '去学习',
                'actionType' => $isChapterCompleted ? 'view' : 'learn',
                'coverImage' => (string)($chapter->cover_image ?? $course->cover_image ?? ''),
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
