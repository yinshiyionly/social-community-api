<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 话题详情资源 - 用于详情/编辑页面
 */
class TopicResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'topicId' => $this->topic_id,
            'topicName' => $this->topic_name,
            'coverUrl' => $this->cover_url,
            'description' => $this->description,
            'detailHtml' => $this->detail_html,
            'creatorId' => $this->creator_id,
            'sortNum' => $this->sort_num,
            'isRecommend' => $this->is_recommend,
            'isOfficial' => $this->is_official,
            'status' => $this->status,
            'postCount' => $this->stat ? $this->stat->post_count : 0,
            'viewCount' => $this->stat ? $this->stat->view_count : 0,
            'followCount' => $this->stat ? $this->stat->follow_count : 0,
            'participantCount' => $this->stat ? $this->stat->participant_count : 0,
            'heatScore' => $this->stat ? $this->stat->heat_score : 0,
            'createdAt' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updatedAt' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
