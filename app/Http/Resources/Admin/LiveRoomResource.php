<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 直播间详情资源 - 用于详情/编辑页面
 */
class LiveRoomResource extends JsonResource
{
    public function toArray($request)
    {
        $stat = $this->relationLoaded('stat') ? $this->stat : null;

        return [
            'roomId'             => $this->room_id,
            'roomTitle'          => $this->room_title,
            'roomCover'          => $this->room_cover,
            'roomIntro'          => $this->room_intro,
            'liveType'           => $this->live_type,
            'livePlatform'       => $this->live_platform,
            'thirdPartyRoomId'   => $this->third_party_room_id,
            'pushUrl'            => $this->push_url,
            'pullUrl'            => $this->pull_url,
            'videoUrl'           => $this->video_url,
            'anchorId'           => $this->anchor_id,
            'anchorName'         => $this->anchor_name,
            'anchorAvatar'       => $this->anchor_avatar,
            'scheduledStartTime' => $this->scheduled_start_time ? $this->scheduled_start_time->format('Y-m-d H:i:s') : null,
            'scheduledEndTime'   => $this->scheduled_end_time ? $this->scheduled_end_time->format('Y-m-d H:i:s') : null,
            'actualStartTime'    => $this->actual_start_time ? $this->actual_start_time->format('Y-m-d H:i:s') : null,
            'actualEndTime'      => $this->actual_end_time ? $this->actual_end_time->format('Y-m-d H:i:s') : null,
            'liveDuration'       => $this->live_duration,
            'liveStatus'         => $this->live_status,
            'liveStatusText'     => $this->live_status_text,
            'allowChat'          => $this->allow_chat,
            'allowGift'          => $this->allow_gift,
            'allowLike'          => $this->allow_like,
            'password'           => $this->password ? '******' : null,
            'extConfig'          => $this->ext_config,
            'status'             => $this->status,
            // 统计数据
            'totalViewerCount'   => $stat ? $stat->total_viewer_count : 0,
            'maxOnlineCount'     => $stat ? $stat->max_online_count : 0,
            'currentOnlineCount' => $stat ? $stat->current_online_count : 0,
            'likeCount'          => $stat ? $stat->like_count : 0,
            'messageCount'       => $stat ? $stat->message_count : 0,
            // 时间
            'createdAt'          => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updatedAt'          => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
