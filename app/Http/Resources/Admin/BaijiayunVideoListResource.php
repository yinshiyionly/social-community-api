<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class BaijiayunVideoListResource extends JsonResource
{
    public function toArray($request)
    {
        $createdAt = $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null;

        return [
            'videoId' => (int) $this->video_id,
            'name' => $this->name,
            'status' => (int) $this->status,
            'statusText' => $this->status_text,
            'publishStatus' => (int) $this->publish_status,
            'publishStatusText' => $this->publish_status_text,
            'totalSize' => (string) $this->total_size,
            'totalSizeText' => $this->formatted_total_size,
            'length' => (int) $this->length,
            'lengthText' => $this->formatted_length,
            'prefaceUrl' => $this->preface_url,
            'playUrl' => $this->play_url,
            'width' => (int) $this->width,
            'height' => (int) $this->height,
            'uploadTime' => $createdAt,
            'createdAt' => $createdAt,
        ];
    }
}

