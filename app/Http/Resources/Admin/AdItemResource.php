<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 广告内容详情资源 - 用于详情/编辑页面
 */
class AdItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'adId' => $this->ad_id,
            'spaceId' => $this->space_id,
            'spaceName' => $this->whenLoaded('adSpace', function () {
                return $this->adSpace ? $this->adSpace->space_name : null;
            }),
            'adTitle' => $this->ad_title,
            'adType' => $this->ad_type,
            'contentUrl' => $this->content_url,
            'targetType' => $this->target_type,
            'targetUrl' => $this->target_url,
            'sortNum' => $this->sort_num,
            'status' => $this->status,
            'startTime' => $this->start_time ? $this->start_time->format('Y-m-d H:i:s') : null,
            'endTime' => $this->end_time ? $this->end_time->format('Y-m-d H:i:s') : null,
            'extJson' => $this->ext_json,
            'createdAt' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updatedAt' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
