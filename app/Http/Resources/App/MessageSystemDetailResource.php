<?php

namespace App\Http\Resources\App;

use App\Constant\MessageType;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 系统消息详情资源类
 */
class MessageSystemDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'messageId' => $this->message_id,
            'title' => $this->title,
            'content' => $this->content,
            'coverUrl' => $this->cover_url,
            'linkType' => $this->link_type,
            'linkTypeName' => $this->link_type ? MessageType::getLinkTypeName($this->link_type) : null,
            'linkUrl' => $this->link_url,
            'createdAt' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
