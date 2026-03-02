<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 直播间列表资源 - 用于列表展示
 */
class LiveRoomListResource extends JsonResource
{
    public function toArray($request)
    {
        $stat = $this->relationLoaded('stat') ? $this->stat : null;

        return [
            'roomId'             => $this->room_id,
            'roomTitle'          => $this->room_title,
            'roomCover'          => $this->room_cover,
            'liveType'           => $this->live_type,
            'livePlatform'       => $this->live_platform,
            'anchorName'         => $this->anchor_name,
            'scheduledStartTime' => $this->scheduled_start_time ? $this->scheduled_start_time->format('Y-m-d H:i:s') : null,
            'scheduledEndTime'   => $this->scheduled_end_time ? $this->scheduled_end_time->format('Y-m-d H:i:s') : null,
            'liveStatus'         => $this->live_status,
            'liveStatusText'     => $this->live_status_text,
            'allowChat'          => $this->allow_chat,
            'allowGift'          => $this->allow_gift,
            'status'             => $this->status,
            'currentOnlineCount' => $stat ? $stat->current_online_count : 0,
            'totalViewerCount'   => $stat ? $stat->total_viewer_count : 0,
            'createdAt'          => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
