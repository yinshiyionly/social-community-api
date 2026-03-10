<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 直播间伪直播素材-点播视频列表资源。
 *
 * 职责：
 * 1. 输出创建直播页面选择点播视频所需的最小字段集；
 * 2. 统一状态文案和时长展示字段，减少前端重复格式化逻辑；
 * 3. 保持字段命名为 camelCase，兼容 Admin 端既有接口约定。
 */
class LiveRoomMockVideoListResource extends JsonResource
{
    /**
     * 输出伪直播点播视频列表项。
     *
     * 字段约定：
     * - videoId 透传自 app_video_baijiayun.video_id，供创建直播接口写入 mockVideoId；
     * - lengthText/statusText/publishStatusText 为展示字段，不参与后端业务计算；
     * - uploadTime 与 createdAt 当前保持一致，兼容历史列表字段读取。
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $createdAt = $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null;

        return [
            'videoId' => (int)$this->video_id,
            'name' => $this->name,
            'prefaceUrl' => $this->preface_url,
            'playUrl' => $this->play_url,
            'length' => (int)$this->length,
            'lengthText' => $this->formatted_length,
            'status' => (int)$this->status,
            'statusText' => $this->status_text,
            'publishStatus' => (int)$this->publish_status,
            'publishStatusText' => $this->publish_status_text,
            'uploadTime' => $createdAt,
            'createdAt' => $createdAt,
        ];
    }
}
