<?php

namespace App\Http\Resources\App;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 课表日视图资源 - 用于学习中心课表日视图
 */
class ScheduleDailyResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'            => $this->id,
            'courseId'       => $this->course_id,
            'chapterId'     => $this->chapter_id,
            'chapterTitle'  => $this->chapter ? $this->chapter->chapter_title : '',
            'chapterNo'     => $this->chapter ? $this->chapter->chapter_no : 0,
            'coverImage'    => $this->chapter ? $this->chapter->cover_image : '',
            'isUnlocked'    => $this->is_unlocked,
            'isLearned'     => $this->is_learned,
            'hasHomework'   => $this->chapter ? $this->chapter->has_homework : 0,
            'homeworkLabel'  => ($this->chapter && $this->chapter->has_homework)
                ? '第' . $this->chapter->chapter_no . '课作业'
                : null,
            'scheduleDate'  => $this->schedule_date ? $this->schedule_date->format('Y-m-d') : null,
            'scheduleTime'  => $this->schedule_time,
        ];
    }
}
