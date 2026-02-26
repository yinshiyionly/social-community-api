<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 直播课章节详情资源 - 用于详情/编辑页面
 */
class LiveChapterResource extends JsonResource
{
    public function toArray($request)
    {
        $live = $this->liveContent;

        return [
            // 章节基础信息
            'chapterId'        => $this->chapter_id,
            'courseId'          => $this->course_id,
            'chapterNo'        => $this->chapter_no,
            'chapterTitle'     => $this->chapter_title,
            'chapterSubtitle'  => $this->chapter_subtitle,
            'coverImage'       => $this->cover_image,
            'brief'            => $this->brief,
            'sortOrder'        => $this->sort_order,
            'status'           => $this->status,
            // 直播信息
            'livePlatform'     => $live ? $live->live_platform : null,
            'liveRoomId'       => $live ? $live->live_room_id : null,
            'livePushUrl'      => $live ? $live->live_push_url : null,
            'livePullUrl'      => $live ? $live->live_pull_url : null,
            'liveCover'        => $live ? $live->live_cover : null,
            'liveStartTime'    => $live && $live->live_start_time ? $live->live_start_time->format('Y-m-d H:i:s') : null,
            'liveEndTime'      => $live && $live->live_end_time ? $live->live_end_time->format('Y-m-d H:i:s') : null,
            'liveDuration'     => $live ? $live->live_duration : null,
            'liveStatus'       => $live ? $live->live_status : null,
            'hasPlayback'      => $live ? $live->has_playback : 0,
            'playbackUrl'      => $live ? $live->playback_url : null,
            'playbackDuration' => $live ? $live->playback_duration : null,
            'allowChat'        => $live ? $live->allow_chat : 1,
            'allowGift'        => $live ? $live->allow_gift : 0,
            'onlineCount'      => $live ? $live->online_count : 0,
            'maxOnlineCount'   => $live ? $live->max_online_count : 0,
            'attachments'      => $live ? $live->attachments : [],
            // 时间
            'createdAt'        => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updatedAt'        => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
