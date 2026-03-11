<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Admin 广告预览列表资源。
 *
 * 字段约定：
 * 1. imageUrl 与 App 端广告字段保持一致，便于后台预览结果复用；
 * 2. linkUrl 为跳转地址透传字段，前端按 targetType 决定打开方式。
 */
class AdPreviewItemResource extends JsonResource
{
    /**
     * 将资源转换为数组。
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'spaceId' => $this->space_id,
            'adId' => $this->ad_id,
            'imageUrl' => $this->content_url,
            'targetType' => $this->target_type,
            'linkUrl' => $this->target_url,
        ];
    }
}
