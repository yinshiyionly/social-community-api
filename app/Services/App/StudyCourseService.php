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
    /**
     * 获取今日学习任务
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
                'time' => $schedule->schedule_time ? $schedule->schedule_time : '',
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
            ->with(['course:course_id,course_title,cover_image,play_type'])
            ->orderByRaw('last_learn_time DESC NULLS LAST')
            ->orderBy('enroll_time', 'desc')
            ->get();

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
                $q->where('category_id', (int) $categoryId);
            });
        }

        // 按付费类型筛选
        if ($payType) {
            $query->whereHas('course', function ($q) use ($payType) {
                $q->where('pay_type', (int) $payType);
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
            $timeStr = $nextSchedule->schedule_time ? $nextSchedule->schedule_time : '';
            $timeText = $timeStr ? ($dateStr . ' ' . $timeStr) : $dateStr;
        }

        // 状态文案
        $statusText = '';
        if ($mc->is_completed) {
            $statusText = '已结课';
        } elseif ($nextSchedule && $nextSchedule->schedule_date) {
            $dateStr = $nextSchedule->schedule_date->format('Y.m.d');
            $timeStr = $nextSchedule->schedule_time ? $nextSchedule->schedule_time : '';
            $statusText = $timeStr ? ($dateStr . ' ' . $timeStr . ' 开课') : ($dateStr . ' 开课');
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
            'timeText' => $timeText,
            'statusText' => $statusText,
            'actionText' => $actionText,
        ];
    }

}
