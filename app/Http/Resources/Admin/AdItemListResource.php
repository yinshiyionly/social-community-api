<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 广告内容列表资源 - 用于列表展示
 */
class AdItemListResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'adId' => $this->ad_id,
            'spaceId' => $this->space_id,
            'spaceName' => $this->adSpace ? $this->adSpace->space_name : null,
            'adTitle' => $this->ad_title,
            'adType' => $this->ad_type,
            'contentUrl' => $this->content_url,
            'targetType' => $this->target_type,
            'targetUrl' => $this->target_url,
            'sortNum' => $this->sort_num,
            'status' => $this->status,
            'startTime' => $this->start_time ? $this->start_time->format('Y-m-d H:i:s') : null,
            'endTime' => $this->end_time ? $this->end_time->format('Y-m-d H:i:s') : null,
            'createdAt' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
