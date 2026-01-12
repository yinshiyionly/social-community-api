<?php

namespace App\Http\Resources\App;

use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'post_id' => $this->post_id,
            'member_id' => $this->member_id,
            'post_type' => $this->post_type,
            'title' => $this->title,
            'content' => $this->content,
            'media_data' => $this->media_data,
            'location_name' => $this->location_name,
            'location_geo' => $this->location_geo,
            'view_count' => $this->view_count,
            'like_count' => $this->like_count,
            'comment_count' => $this->comment_count,
            'share_count' => $this->share_count,
            'collection_count' => $this->collection_count,
            'is_top' => $this->is_top,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
