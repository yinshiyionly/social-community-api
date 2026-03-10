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
            'liveType'           => (int)$this->live_type,
            'thirdPartyRoomId'   => (int)$this->third_party_room_id,
            'anchorName'         => $this->anchor_name,
            'adminCode'          => $this->admin_code ?? null,
            'teacherCode'        => $this->teacher_code ?? null,
            'studentCode'        => $this->student_code ?? null,
            'scheduledStartTime' => $this->scheduled_start_time ? $this->scheduled_start_time->format('Y-m-d H:i:s') : null,
            'scheduledEndTime'   => $this->scheduled_end_time ? $this->scheduled_end_time->format('Y-m-d H:i:s') : null,
            'enableLiveSell'     => $this->enable_live_sell ?? null,
            'appTemplate'        => $this->app_template ?? null,
            'mockVideoSource'    => $this->mock_video_course ?? null,
            'mockVideoId'        => $this->mock_video_id ?? null,
            'mockRoomId'         => $this->mock_room_id ?? null,
            'liveStatus'         => $this->live_status,
            'liveStatusText'     => $this->live_status_text,
            'status'             => $this->status,
        ];
    }
}
