<?php

namespace App\Http\Resources\App;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 积分流水列表资源
 */
class PointLogListResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'logId' => $this->log_id,
            'title' => $this->title,
            'changeValue' => $this->change_value,
            'changeType' => $this->change_type,
            'createdAt' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
