<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 话题简单资源 - 用于下拉选项
 */
class TopicSimpleResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'topicId' => $this->topic_id,
            'topicName' => $this->topic_name,
        ];
    }
}
