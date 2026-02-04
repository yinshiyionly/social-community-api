<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 讲师简单资源 - 用于下拉选项/关联引用
 */
class CourseTeacherSimpleResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'teacherId' => $this->teacher_id,
            'teacherName' => $this->teacher_name,
            'avatar' => $this->avatar,
            'title' => $this->title,
        ];
    }
}
