<?php

namespace App\Services\App;

use App\Models\App\AppCourseBase;
use Illuminate\Support\Collection;

class CourseService
{
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
