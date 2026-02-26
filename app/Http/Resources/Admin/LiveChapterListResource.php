<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 直播课章节列表资源 - 用于列表展示
 */
class LiveChapterListResource extends JsonResource
{
    public function toArray($request)
    {
        $live = $this->liveContent;

        return [
            'chapterId'     => $this->chapter_id,
            'chapterNo'     => $this->chapter_no,
            'chapterTitle'  => $this->chapter_title,
            'liveStatus'    => $live ? $live->live_status : null,
            'liveStartTime' => $live && $live->live_start_time ? $live->live_start_time->format('Y-m-d H:i:s') : null,
            'liveEndTime'   => $live && $live->live_end_time ? $live->live_end_time->format('Y-m-d H:i:s') : null,
            'liveRoomId'    => $live ? $live->live_room_id : null,
            'onlineCount'   => $live ? $live->online_count : 0,
            'hasPlayback'   => $live ? $live->has_playback : 0,
            'createdAt'     => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
