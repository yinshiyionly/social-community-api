<?php

namespace App\Http\Resources\Admin;

use App\Models\App\AppCourseBase;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 课表资源 - 课程 + 章节 + 章节内容聚合
 */
class CourseScheduleResource extends JsonResource
{
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
            'teacherId' => $this->teacher_id,
            'chapters' => $this->whenLoaded('chapters', function () {
                return CourseScheduleChapterResource::collection(
                    $this->chapters->sortBy('sort_order')->values()
                );
            }),
        ];
    }
}
