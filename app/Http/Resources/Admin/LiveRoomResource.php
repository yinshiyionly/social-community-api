<?php

namespace App\Http\Resources\Admin;

use App\Models\App\AppLiveRoom;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 直播间详情资源 - 用于详情/编辑页面
 */
class LiveRoomResource extends JsonResource
{
    /**
     * 输出直播间详情字段。
     *
     * 字段约定：
     * - 返回字段统一使用 camelCase；
     * - 伪直播扩展字段仅在 liveType=2 时返回真实值，其他类型固定返回 null，避免前端误用脏数据；
     * - 时间字段统一格式化为 Y-m-d H:i:s。
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $stat = $this->relationLoaded('stat') ? $this->stat : null;
        $isPseudoLive = (int)$this->live_type === AppLiveRoom::LIVE_TYPE_PSEUDO;

        return [
            'roomId'             => $this->room_id,
            'roomTitle'          => $this->room_title,
            'roomCover'          => $this->room_cover,
            'liveType'           => $this->live_type,
            'thirdPartyRoomId'   => $this->third_party_room_id,
            'anchorName'         => $this->anchor_name,
            'scheduledStartTime' => $this->scheduled_start_time ? $this->scheduled_start_time->format('Y-m-d H:i:s') : null,
            'scheduledEndTime'   => $this->scheduled_end_time ? $this->scheduled_end_time->format('Y-m-d H:i:s') : null,
            'enableLiveSell'     => $this->enable_live_sell,
            'mockVideoSource'    => $isPseudoLive ? $this->mock_video_source : null,
            'mockVideoId'        => $isPseudoLive ? $this->mock_video_id : null,
            'mockRoomId'         => $isPseudoLive ? $this->mock_room_id : null,
            'liveStatus'         => $this->live_status,
            'liveStatusText'     => $this->live_status_text,
            'status'             => $this->status,
        ];
    }
}
