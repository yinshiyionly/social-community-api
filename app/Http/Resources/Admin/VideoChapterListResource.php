<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 录播课章节列表资源 - 用于列表展示
 */
class VideoChapterListResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'chapterId' => $this->chapter_id,
            'courseId' => $this->course_id,
            'chapterTitle' => $this->chapter_title,
            'isFreeTrial' => $this->is_free_trial,
            'status' => $this->status,
            'sortOrder' => $this->sort_order,
            'unlockTime' => $this->unlock_time ? $this->unlock_time->format('Y-m-d H:i:s') : null,
            'createTime' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
