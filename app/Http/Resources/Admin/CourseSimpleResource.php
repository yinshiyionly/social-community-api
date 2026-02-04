<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 课程简单资源 - 用于下拉选项/关联引用
 */
class CourseSimpleResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'courseId' => $this->course_id,
            'courseNo' => $this->course_no,
            'courseTitle' => $this->course_title,
        ];
    }
}
