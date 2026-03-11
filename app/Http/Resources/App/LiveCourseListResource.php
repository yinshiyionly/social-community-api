<?php

namespace App\Http\Resources\App;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * App 端课程页「大咖直播」列表资源。
 *
 * 字段约束：
 * 1. 统一输出 live/upcoming/replay 三种状态卡片；
 * 2. 输出字段采用 camelCase，保持前端读取口径一致；
 * 3. replay 场景下 id 为 third_party_room_id（字符串）。
 */
class LiveCourseListResource extends JsonResource
{
    /**
     * 输出课程页大咖直播卡片。
     *
     * 字段约定：
     * - startTime 统一为 `Y-m-d H:i:s`；
     * - reserveCount/isReserved 仅 upcoming 有业务含义，其余状态返回兜底值；
     * - watchCount/liveToken 仅 live/replay 有业务含义，其余状态返回兜底值。
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $status = (string)($this['status'] ?? '');
        $isReserved = (bool)($this['isReserved'] ?? false);

        return [
            'id' => $status === 'replay'
                ? (string)($this['id'] ?? '')
                : (int)($this['id'] ?? 0),
            'title' => (string)($this['title'] ?? ''),
            'cover' => (string)($this['cover'] ?? ''),
            'startTime' => (string)($this['startTime'] ?? ''),
            'status' => $status,
            'reserveCount' => (int)($this['reserveCount'] ?? 0),
            'isReserved' => $isReserved,
            'watchCount' => (int)($this['watchCount'] ?? 0),
            'liveToken' => (string)($this['liveToken'] ?? ''),
            'actionText' => (string)($this['actionText'] ?? $this->buildDefaultActionText($status, $isReserved)),
        ];
    }

    /**
     * 构建 actionText 默认值，避免上游漏传导致前端文案为空。
     *
     * @param string $status
     * @param bool $isReserved
     * @return string
     */
    protected function buildDefaultActionText(string $status, bool $isReserved): string
    {
        if ($status === 'live') {
            return '进入直播';
        }

        if ($status === 'upcoming') {
            return $isReserved ? '已预约' : '预约';
        }

        if ($status === 'replay') {
            return '看回放';
        }

        return '';
    }
}
