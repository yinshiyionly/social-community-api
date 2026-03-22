<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 直播课章节详情资源
 */
class LiveChapterResource extends JsonResource
{
    /**
     * 输出直播章节详情。
     *
     * 字段约定：
     * 1. 字段名与直播章节创建/更新入参保持一致，便于编辑页直接回填；
     * 2. unlockDate 统一按 `Y-m-d` 返回，章节时间按 `Y-m-d H:i:s` 返回；
     * 3. liveRoomId 返回直播内容快照中的 live_room_id。
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'chapterId' => $this->chapter_id,
            'courseId' => $this->course_id,
            'chapterTitle' => $this->chapter_title,
            'chapterSubtitle' => $this->chapter_subtitle,
            'coverImage' => $this->cover_image,
            'liveRoomId' => $this->liveContent ? $this->liveContent->live_room_id : null,
            'isFree' => $this->is_free,
            'unlockType' => $this->unlock_type,
            'unlockDays' => $this->unlock_days,
            'unlockDate' => $this->unlock_date ? $this->unlock_date->format('Y-m-d') : null,
            'chapterStartTime' => $this->chapter_start_time ? $this->chapter_start_time->format('Y-m-d H:i:s') : null,
            'chapterEndTime' => $this->chapter_end_time ? $this->chapter_end_time->format('Y-m-d H:i:s') : null,
            'liveStatus' => $this->liveContent ? $this->liveContent->live_status : null,
            'liveStatusText' => $this->liveContent ? $this->liveContent->live_status_text : null,
            'sortOrder' => $this->sort_order,
            'status' => $this->status,
            'createTime' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updateTime' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
