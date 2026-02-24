<?php

namespace App\Http\Resources\App;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 用户课程列表资源 - 用于学习中心我的课程列表
 */
class MemberCourseListResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'              => $this->id,
            'courseId'         => $this->course_id,
            'courseTitle'      => $this->course ? $this->course->course_title : '',
            'coverImage'      => $this->course ? $this->course->cover_image : '',
            'progress'         => $this->progress,
            'learnedChapters'  => $this->learned_chapters,
            'totalChapters'    => $this->total_chapters,
            'isCompleted'      => $this->is_completed,
            'lastLearnTime'    => $this->last_learn_time ? $this->last_learn_time->format('Y-m-d H:i:s') : null,
            'enrollTime'       => $this->enroll_time ? $this->enroll_time->format('Y-m-d H:i:s') : null,
        ];
    }
}
