<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 直播课章节列表资源
 */
class LiveChapterListResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'chapterId' => $this->chapter_id,
            'courseId' => $this->course_id,
            'chapterNo' => $this->chapter_no,
            'chapterTitle' => $this->chapter_title,
            'isFree' => $this->is_free,
            'liveStartTime' => $this->chapter_start_time ? $this->chapter_start_time->format('Y-m-d H:i:s') : null,
            'liveEndTime' => $this->chapter_end_time ? $this->chapter_end_time->format('Y-m-d H:i:s') : null,
            'liveStatus' => $this->liveContent ? $this->liveContent->live_status : null,
            'liveStatusText' => $this->liveContent ? $this->liveContent->live_status_text : null,
            'roomId' => $this->liveContent ? $this->liveContent->live_room_id : null,
            'sortOrder' => $this->sort_order,
            'status' => $this->status,
            'createTime' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
