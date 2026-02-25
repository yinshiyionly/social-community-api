<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 广告位详情资源 - 用于详情/编辑页面
 */
class AdSpaceResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'spaceId' => $this->space_id,
            'spaceName' => $this->space_name,
            'spaceCode' => $this->space_code,
            'platform' => $this->platform,
            'width' => $this->width,
            'height' => $this->height,
            'maxAds' => $this->max_ads,
            'status' => $this->status,
            'createdAt' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updatedAt' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
