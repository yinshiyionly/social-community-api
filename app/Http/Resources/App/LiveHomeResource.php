<?php

namespace App\Http\Resources\App;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 直播首页聚合响应资源。
 *
 * 职责：
 * 1. 输出 latest、tab、list、分页字段；
 * 2. 复用 LiveHomeItemResource，统一卡片字段口径；
 * 3. 控制器仅负责调用，不承载字段裁剪细节。
 */
class LiveHomeResource extends JsonResource
{
    /**
     * 输出直播首页响应结构。
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $latest = $this['latest'] ?? null;
        $list = collect($this['list'] ?? []);

        return [
            'latest' => $latest ? (new LiveHomeItemResource($latest))->resolve() : null,
            'tab' => (string)($this['tab'] ?? ''),
            'list' => LiveHomeItemResource::collection($list)->resolve(),
            'total' => (int)($this['total'] ?? 0),
            'page' => (int)($this['page'] ?? 1),
            'pageSize' => (int)($this['pageSize'] ?? 0),
            'hasMore' => (bool)($this['hasMore'] ?? false),
        ];
    }
}
