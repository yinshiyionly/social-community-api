<?php

namespace App\Services\App;

use App\Models\App\AppCourseBase;
use App\Models\App\AppCourseCategory;
use Illuminate\Support\Collection;

class CourseService
{
    /**
     * 获取课程分类列表
     *
     * @return Collection
     */
    public function getCategories(): Collection
    {
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

        $payTypeNames = [
            AppCourseBase::PAY_TYPE_TRIAL => '招生0元课',
            AppCourseBase::PAY_TYPE_BEGINNER => '进阶课',
            AppCourseBase::PAY_TYPE_ADVANCED => '高阶课',
            AppCourseBase::PAY_TYPE_PAID => '付费课',
        ];

        $result = [];
        foreach ($payTypeNames as $payType => $name) {
            if ($grouped->has($payType)) {
                $result[] = [
                    'payType' => $payType,
                    'payTypeName' => $name,
                    'courses' => $grouped->get($payType)->values(),
                ];
            }
        }

        return $result;
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
