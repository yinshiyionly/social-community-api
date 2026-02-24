<?php

namespace App\Services\App;

use App\Models\App\AppCourseBase;
use App\Models\App\AppCourseCategory;
use App\Models\App\AppMemberCourse;
use App\Services\App\LearningCenterService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CourseService
{
    /**
     * 获取课程分类列表
     *
     * @return Collection
     */
    public function getCategories($limit = 0): Collection
    {
        if (!empty($limit)) {
            return AppCourseCategory::query()
                ->select(['category_id', 'category_name', 'icon'])
                ->enabled()
                ->topLevel()
                ->orderByDesc('sort_order')
                ->limit($limit)
                ->get();
        }
        return AppCourseCategory::query()
            ->select(['category_id', 'category_name', 'icon'])
            ->enabled()
            ->topLevel()
            ->orderByDesc('sort_order')
            ->get();
    }

    /**
     * 获取选课中心课程列表（按付费类型分组）
     *
     * @param int $categoryId
     * @return array
     */
    /**
     * 获取选课中心课程列表（按付费类型分组）
     *
     * @param int $categoryId
     * @return array
     */
    public function getCoursesByCategory(int $categoryId): array
    {
        $courses = AppCourseBase::query()
            ->select([
                'course_id',
                'course_title',
                'course_subtitle',
                'cover_image',
                'current_price',
                'original_price',
                'pay_type',
            ])
            ->online()
            ->where('category_id', $categoryId)
            ->orderByDesc('sort_order')
            ->get();

        // 按付费类型分组
        $grouped = $courses->groupBy('pay_type');

        $result = [];
        foreach (AppCourseBase::PAY_TYPE_CONFIG as $payType => $config) {
            if ($grouped->has($payType)) {
                $result[] = array_merge($config, [
                    'list' => $grouped->get($payType)->values(),
                ]);
            }
        }

        return $result;
    }

    /**
     * 获取课程详情（简单版，用于领取/购买校验）
     *
     * @param int $courseId
     * @return AppCourseBase|null
     */
    public function getCourseDetail(int $courseId): ?AppCourseBase
    {
        return AppCourseBase::query()
            ->select([
                'course_id',
                'course_title',
                'current_price',
                'original_price',
                'is_free',
                'valid_days',
                'total_chapter',
            ])
            ->online()
            ->where('course_id', $courseId)
            ->first();
    }

    /**
     * 获取课程完整详情
     *
     * @param int $courseId
     * @return AppCourseBase|null
     */
    public function getCourseFullDetail(int $courseId): ?AppCourseBase
    {
        $course = AppCourseBase::query()
            ->with([
                'teacher:teacher_id,teacher_name,avatar,title,brief',
                'category:category_id,category_name',
            ])
            ->online()
            ->where('course_id', $courseId)
            ->first();

        if ($course) {
            // 增加浏览次数
            AppCourseBase::where('course_id', $courseId)->increment('view_count');
        }

        return $course;
    }

    /**
     * 检查用户是否已拥有课程
     *
     * @param int $memberId
     * @param int $courseId
     * @return bool
     */
    public function checkUserHasCourse(int $memberId, int $courseId): bool
    {
        return AppMemberCourse::hasCourse($memberId, $courseId);
    }

    /**
     * 免费领取课程
     *
     * @param int $memberId
     * @param int $courseId
     * @param string $phone
     * @param string $ageRange
     * @return AppMemberCourse
     * @throws \Exception
     */
    public function claimFreeCourse(int $memberId, int $courseId, string $phone, string $ageRange): AppMemberCourse
    {
        $course = $this->getCourseDetail($courseId);

        if (!$course) {
            throw new \Exception('课程不存在');
        }

        if ($course->is_free != 1) {
            throw new \Exception('该课程不是免费课程');
        }

        if (AppMemberCourse::hasCourse($memberId, $courseId)) {
            throw new \Exception('您已领取过该课程');
        }

        return DB::transaction(function () use ($memberId, $courseId, $phone, $ageRange, $course) {
            $expireTime = null;
            if ($course->valid_days > 0) {
                $expireTime = now()->addDays($course->valid_days);
            }

            $memberCourse = AppMemberCourse::create([
                'member_id' => $memberId,
                'course_id' => $courseId,
                'source_type' => AppMemberCourse::SOURCE_TYPE_FREE,
                'enroll_phone' => $phone,
                'enroll_age_range' => $ageRange,
                'paid_amount' => 0,
                'enroll_time' => now(),
                'expire_time' => $expireTime,
                'total_chapters' => $course->total_chapter,
            ]);

            // 更新课程报名人数
            AppCourseBase::where('course_id', $courseId)->increment('enroll_count');

            // 生成用户课表
            $learningCenterService = new LearningCenterService();
            $learningCenterService->generateSchedule(
                $memberId,
                $courseId,
                $memberCourse->id,
                now()->toDateTime()
            );

            return $memberCourse;
        });
    }

    /**
     * 购买课程（创建待支付记录）
     *
     * @param int $memberId
     * @param int $courseId
     * @param string $phone
     * @param string $ageRange
     * @return array
     * @throws \Exception
     */
    public function preparePurchase(int $memberId, int $courseId, string $phone, string $ageRange): array
    {
        $course = $this->getCourseDetail($courseId);

        if (!$course) {
            throw new \Exception('课程不存在');
        }

        if ($course->is_free == 1) {
            throw new \Exception('该课程是免费课程，请直接领取');
        }

        if (AppMemberCourse::hasCourse($memberId, $courseId)) {
            throw new \Exception('您已购买过该课程');
        }

        // 返回支付信息，供前端调起支付
        return [
            'courseId' => $courseId,
            'courseTitle' => $course->course_title,
            'price' => $course->current_price,
            'originalPrice' => $course->original_price,
            'phone' => $phone,
            'ageRange' => $ageRange,
        ];
    }

    /**
     * 获取好课上新列表
     *
     * @param int $limit
     * @return Collection
     */
    public function getNewCourses(int $limit = 10): Collection
    {
        return AppCourseBase::query()
            ->select([
                'course_id',
                'course_title',
                'cover_image',
                'current_price',
                'original_price',
                'teacher_id',
            ])
            ->with('teacher:teacher_id,teacher_name,brief')
            ->online()
            ->where('is_new', 1)
            ->orderByDesc('publish_time')
            ->orderByDesc('sort_order')
            ->limit($limit)
            ->get();
    }

    /**
     * 获取名师好课列表
     *
     * @param int $limit
     * @return Collection
     */
    public function getRecommendCourses(int $limit = 10): Collection
    {
        return AppCourseBase::query()
            ->select([
                'course_id',
                'course_title',
                'cover_image',
                'current_price',
                'original_price',
                'teacher_id',
            ])
            ->with('teacher:teacher_id,teacher_name,brief')
            ->online()
            ->where('is_recommend', 1)
            ->orderByDesc('sort_order')
            ->orderByDesc('enroll_count')
            ->limit($limit)
            ->get();
    }
}
