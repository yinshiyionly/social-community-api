<?php

namespace App\Services\App;

use App\Models\App\AppCourseBase;
use App\Models\App\AppCourseCategory;
use App\Models\App\AppMemberCourse;
use App\Models\App\AppMemberSchedule;
use Illuminate\Support\Facades\Log;

/**
 * 学习页（课程Tab）服务
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
     * 获取学习页总览数据
     *
     * @param int $memberId
     * @return array
     */
    public function getOverview(int $memberId): array
    {
        // 今日任务：今日课表
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
            $course = $schedule->course;

            $statusText = '待学习';
            if ($schedule->is_learned) {
                $statusText = '已学完';
            } elseif ($schedule->schedule_time) {
                $statusText = '待开课';
            }

            $todayTasks[] = [
                'id' => $schedule->id,
                'time' => $schedule->schedule_time ? $schedule->schedule_time : '',
                'title' => $chapter ? $chapter->chapter_title : '',
                'subtitle' => $chapter ? $chapter->chapter_subtitle : '',
                'statusText' => $statusText,
            ];
        }

        // 查询用户所有未过期课程，预加载课程信息
        $memberCourses = AppMemberCourse::byMember($memberId)
            ->notExpired()
            ->select([
                'id', 'course_id', 'progress', 'learned_chapters', 'total_chapters',
                'is_completed', 'last_learn_time', 'last_chapter_id', 'enroll_time',
            ])
            ->with(['course:course_id,course_title,cover_image,play_type'])
            ->orderByRaw('last_learn_time DESC NULLS LAST')
            ->orderBy('enroll_time', 'desc')
            ->get();

        // 分组：最近学习（有学习记录且未完课）、待学习（无学习记录且未完课）、已结课
        $recentList = [];
        $pendingList = [];
        $finishedList = [];

        foreach ($memberCourses as $mc) {
            $course = $mc->course;
            if (!$course) {
                continue;
            }

            $item = $this->formatCourseOverviewItem($mc, $course);

            if ($mc->is_completed) {
                $finishedList[] = $item;
            } elseif ($mc->last_learn_time) {
                $recentList[] = $item;
            } else {
                $pendingList[] = $item;
            }
        }

        return [
            'todayTasks' => $todayTasks,
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
     * @param string $filter 筛选值
     * @param string $filterType 筛选类型 quick|allType
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getFilteredCourseList(int $memberId, string $filter, string $filterType, int $page, int $pageSize): array
    {
        $query = AppMemberCourse::byMember($memberId)
            ->notExpired()
            ->select([
                'id', 'course_id', 'progress', 'learned_chapters', 'total_chapters',
                'is_completed', 'last_learn_time', 'enroll_time',
            ]);

        // 根据筛选类型关联课程表进行过滤
        if ($filterType === 'quick') {
            // 按课程分类筛选
            $query->whereHas('course', function ($q) use ($filter) {
                $q->where('category_id', (int) $filter);
            });
        } elseif ($filterType === 'allType') {
            // 按付费类型筛选
            $query->whereHas('course', function ($q) use ($filter) {
                $q->where('pay_type', (int) $filter);
            });
        }

        $query->with(['course:course_id,course_title,cover_image,play_type,pay_type'])
            ->orderByRaw('last_learn_time DESC NULLS LAST')
            ->orderBy('enroll_time', 'desc');

        $paginator = $query->paginate($pageSize, ['*'], 'page', $page);

        $list = [];
        foreach ($paginator->items() as $mc) {
            $course = $mc->course;
            if (!$course) {
                continue;
            }
            $list[] = $this->formatCourseListItem($mc, $course);
        }

        return [
            'list' => $list,
            'total' => $paginator->total(),
            'page' => $paginator->currentPage(),
            'pageSize' => $paginator->perPage(),
        ];
    }

    /**
     * 格式化总览课程项
     *
     * @param AppMemberCourse $mc
     * @param AppCourseBase $course
     * @return array
     */
    private function formatCourseOverviewItem(AppMemberCourse $mc, AppCourseBase $course): array
    {
        $overlayText = '';
        $payTypeConfig = isset(AppCourseBase::PAY_TYPE_CONFIG[$course->pay_type])
            ? AppCourseBase::PAY_TYPE_CONFIG[$course->pay_type]
            : null;
        if ($payTypeConfig) {
            $overlayText = $payTypeConfig['typeName'];
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
     * @return array
     */
    private function formatCourseListItem(AppMemberCourse $mc, AppCourseBase $course): array
    {
        $overlayText = '';
        $payTypeConfig = isset(AppCourseBase::PAY_TYPE_CONFIG[$course->pay_type])
            ? AppCourseBase::PAY_TYPE_CONFIG[$course->pay_type]
            : null;
        if ($payTypeConfig) {
            $overlayText = $payTypeConfig['typeName'];
        }

        // 状态文案
        $statusText = '';
        if ($mc->is_completed) {
            $statusText = '已结课';
        } elseif ($mc->progress > 0) {
            $statusText = '已学' . (int) $mc->progress . '%';
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
            'statusText' => $statusText,
            'actionText' => $actionText,
        ];
    }
}
