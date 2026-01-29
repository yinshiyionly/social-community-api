<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 话题列表资源 - 用于列表展示
 */
class TopicListResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'topicId' => $this->topic_id,
            'topicName' => $this->topic_name,
            'coverUrl' => $this->cover_url,
            'description' => $this->description,
            'sortNum' => $this->sort_num,
            'isRecommend' => $this->is_recommend,
            'isOfficial' => $this->is_official,
            'status' => $this->status,
            'postCount' => $this->stat ? $this->stat->post_count : 0,
            'viewCount' => $this->stat ? $this->stat->view_count : 0,
            'followCount' => $this->stat ? $this->stat->follow_count : 0,
            'createdAt' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
