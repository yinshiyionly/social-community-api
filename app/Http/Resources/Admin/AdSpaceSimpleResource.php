<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 广告位简单资源 - 用于下拉选项
 */
class AdSpaceSimpleResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'spaceId' => $this->space_id,
            'spaceName' => $this->space_name,
            'spaceCode' => $this->space_code,
        ];
    }
}
