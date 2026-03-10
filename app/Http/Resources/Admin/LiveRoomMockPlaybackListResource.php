<?php

namespace App\Http\Resources\Admin;

use App\Models\App\AppLivePlayback;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 直播间伪直播素材-回放列表资源。
 *
 * 职责：
 * 1. 输出创建伪直播页面选择回放所需的最小字段集；
 * 2. 统一回放状态/屏蔽状态文案与时长展示文案；
 * 3. 保持字段命名为 camelCase，兼容 Admin 端既有接口约定。
 */
class LiveRoomMockPlaybackListResource extends JsonResource
{
    /**
     * 输出伪直播回放列表项。
     *
     * 字段约定：
     * - mockRoomId 透传自 app_live_playback.third_party_room_id；
     * - lengthText/statusText/publishStatusText 为展示字段，不参与后端业务计算；
     * - createTime 使用百家云回放生成时间（create_time），与本地 created_at 语义不同。
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $length = isset($this->length) ? (int)$this->length : 0;
        $status = isset($this->status) ? (int)$this->status : AppLivePlayback::STATUS_GENERATING;
        $publishStatus = isset($this->publish_status)
            ? (int)$this->publish_status
            : AppLivePlayback::PUBLISH_STATUS_UNSHIELDED;

        return [
            'mockRoomId' => (string)$this->third_party_room_id,
            'name' => $this->name,
            'prefaceUrl' => $this->preface_url,
            'playUrl' => $this->play_url,
            'length' => $length,
            'lengthText' => $this->formatLength($length),
            'status' => $status,
            'statusText' => $this->getStatusText($status),
            'publishStatus' => $publishStatus,
            'publishStatusText' => $this->getPublishStatusText($publishStatus),
            'createTime' => $this->formatDateTime($this->create_time),
        ];
    }

    /**
     * 格式化回放时长（HH:MM:SS）。
     *
     * @param int $seconds
     * @return string
     */
    protected function formatLength(int $seconds): string
    {
        $seconds = max($seconds, 0);
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $remainSeconds = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $remainSeconds);
    }

    /**
     * 获取回放状态文本。
     *
     * @param int $status
     * @return string
     */
    protected function getStatusText(int $status): string
    {
        $map = [
            AppLivePlayback::STATUS_GENERATING => '生成中',
            AppLivePlayback::STATUS_TRANSCODING => '转码中',
            AppLivePlayback::STATUS_TRANSCODE_FAILED => '转码失败',
            AppLivePlayback::STATUS_TRANSCODE_SUCCESS => '转码成功',
        ];

        return $map[$status] ?? '未知状态';
    }

    /**
     * 获取屏蔽状态文本。
     *
     * @param int $publishStatus
     * @return string
     */
    protected function getPublishStatusText(int $publishStatus): string
    {
        $map = [
            AppLivePlayback::PUBLISH_STATUS_UNSHIELDED => '未屏蔽',
            AppLivePlayback::PUBLISH_STATUS_SHIELDED => '已屏蔽',
        ];

        return $map[$publishStatus] ?? '未知状态';
    }

    /**
     * 标准化时间字符串输出。
     *
     * @param mixed $datetime
     * @return string|null
     */
    protected function formatDateTime($datetime): ?string
    {
        if ($datetime instanceof \DateTimeInterface) {
            return $datetime->format('Y-m-d H:i:s');
        }

        if (!is_string($datetime) || trim($datetime) === '') {
            return null;
        }

        $timestamp = strtotime($datetime);
        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d H:i:s', $timestamp);
    }
}
