<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 直播课课程表章节资源。
 *
 * 职责：
 * 1. 输出课程表页所需的章节基础字段；
 * 2. 平铺返回直播字段，减少前端二次字段映射成本；
 * 3. 仅做字段转换，不承载业务判断逻辑。
 */
class LiveCourseSheetChapterResource extends JsonResource
{
    /**
     * 输出直播课课程表章节项。
     *
     * 字段约定：
     * 1. 直播字段全部平铺返回，避免前端拆解 liveContent；
     * 2. unlockDate 使用 `Y-m-d`，时间字段使用 `Y-m-d H:i:s`；
     * 3. 数值字段缺失时回退 0，字符串字段缺失时返回 null。
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $liveContent = $this->liveContent;

        return [
            'chapterId' => $this->chapter_id,
            'courseId' => $this->course_id,
            'chapterTitle' => $this->chapter_title,
            'chapterSubtitle' => $this->chapter_subtitle,
            'coverImage' => $this->cover_image,
            'liveRoomId' => $liveContent ? $liveContent->live_room_id : null,
            'livePlatform' => $liveContent ? $liveContent->live_platform : null,
            'liveCover' => $liveContent ? $liveContent->live_cover : null,
            'liveStartTime' => $liveContent && $liveContent->live_start_time
                ? $liveContent->live_start_time->format('Y-m-d H:i:s')
                : null,
            'liveEndTime' => $liveContent && $liveContent->live_end_time
                ? $liveContent->live_end_time->format('Y-m-d H:i:s')
                : null,
            'liveDuration' => $liveContent ? (int)$liveContent->live_duration : 0,
            'liveStatus' => $liveContent ? (int)$liveContent->live_status : 0,
            'liveStatusText' => $liveContent ? $liveContent->live_status_text : '未知',
            'hasPlayback' => $liveContent ? (int)$liveContent->has_playback : 0,
            'playbackUrl' => $liveContent ? $liveContent->playback_url : null,
            'allowChat' => $liveContent ? (int)$liveContent->allow_chat : 0,
            'onlineCount' => $liveContent ? (int)$liveContent->online_count : 0,
            'maxOnlineCount' => $liveContent ? (int)$liveContent->max_online_count : 0,
            'isFree' => (int)$this->is_free,
            'unlockType' => (int)$this->unlock_type,
            'unlockDays' => (int)$this->unlock_days,
            'unlockDate' => $this->unlock_date ? $this->unlock_date->format('Y-m-d') : null,
            'chapterStartTime' => $this->chapter_start_time ? $this->chapter_start_time->format('Y-m-d H:i:s') : null,
            'chapterEndTime' => $this->chapter_end_time ? $this->chapter_end_time->format('Y-m-d H:i:s') : null,
            'status' => (int)$this->status,
            'sortOrder' => (int)$this->sort_order,
            'createTime' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
