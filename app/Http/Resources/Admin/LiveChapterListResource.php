<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 直播课章节列表资源
 */
class LiveChapterListResource extends JsonResource
{
    /**
     * 输出直播章节列表项。
     *
     * 字段约定：
     * 1. 字段名统一使用管理端 camelCase 命名；
     * 2. 解锁字段与创建/更新入参保持同名，便于前端表单回填；
     * 3. liveRoomId 来源于直播内容表快照字段 live_room_id。
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
        ];
    }
}
