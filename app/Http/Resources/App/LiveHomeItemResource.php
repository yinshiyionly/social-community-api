<?php

namespace App\Http\Resources\App;

use App\Http\Requests\App\LiveHomeRequest;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 直播首页单条卡片资源。
 *
 * 职责：
 * 1. 统一 upcoming/replay 两种卡片的输出字段；
 * 2. 保持字段命名为 App 端约定的 camelCase；
 * 3. 在资源层裁剪差异字段，避免控制器拼接分支。
 */
class LiveHomeItemResource extends JsonResource
{
    /**
     * 输出直播首页单条卡片。
     *
     * 字段约定：
     * - upcoming 返回 reserveCount/isReserved；
     * - replay 返回 watchCount/durationSec/replayUrl/liveToken；
     * - status 仅输出 `upcoming` 或 `replay`。
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $status = (string)($this['status'] ?? '');

        $item = [
            'id' => (int)($this['id'] ?? 0),
            'title' => (string)($this['title'] ?? ''),
            'cover' => (string)($this['cover'] ?? ''),
            'startTime' => (string)($this['startTime'] ?? ''),
            'status' => $status,
            'actionText' => (string)($this['actionText'] ?? ''),
        ];

        if ($status === LiveHomeRequest::TAB_REPLAY) {
            $item['watchCount'] = (int)($this['watchCount'] ?? 0);
            $item['durationSec'] = (int)($this['durationSec'] ?? 0);
            $item['replayUrl'] = (string)($this['replayUrl'] ?? '');
            $item['liveToken'] = (string)($this['player_token'] ?? '');

            return $item;
        }

        $item['reserveCount'] = (int)($this['reserveCount'] ?? 0);
        $item['isReserved'] = (bool)($this['isReserved'] ?? false);

        return $item;
    }
}
