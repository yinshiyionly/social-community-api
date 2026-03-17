<?php

namespace App\Http\Resources\Admin;

use App\Models\App\AppCourseBase;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 课表资源 - 课程 + 章节 + 章节内容聚合
 */
class CourseScheduleResource extends JsonResource
{
    /**
     * 输出课表详情聚合数据（课程头 + 章节列表）。
     *
     * 字段约定：
     * 1. teacherName 直接读取 app_course_base.teacher_name；
     * 2. chapters 仅在预加载时返回，避免额外查询开销。
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'courseId' => $this->course_id,
            'courseTitle' => $this->course_title,
            'courseSubtitle' => $this->course_subtitle,
            'playType' => $this->play_type,
            'scheduleType' => $this->schedule_type,
            'status' => $this->status,
            'totalChapter' => $this->total_chapter,
            'totalDuration' => $this->total_duration,
            'coverImage' => $this->cover_image,
            'teacherName' => $this->teacher_name,
            'chapters' => $this->whenLoaded('chapters', function () {
                return CourseScheduleChapterResource::collection(
                    $this->chapters->sortBy('sort_order')->values()
                );
            }),
        ];
    }
}
