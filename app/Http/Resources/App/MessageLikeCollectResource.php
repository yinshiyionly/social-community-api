<?php

namespace App\Http\Resources\App;

use App\Constant\MessageType;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 赞和收藏消息列表资源类
 */
class MessageLikeCollectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        $sender = $this->sender;

        return [
            'messageId' => $this->message_id,
            'type' => $this->message_type,
            'typeName' => MessageType::getTypeName($this->message_type),
            'sender' => $sender ? [
                'memberId' => $sender->member_id,
                'nickname' => $sender->nickname,
                'avatar' => $sender->avatar,
            ] : null,
            'targetId' => $this->target_id,
            'targetType' => $this->target_type,
            'targetTypeName' => $this->target_type ? MessageType::getTargetTypeName($this->target_type) : null,
            'contentSummary' => $this->content_summary,
            'coverUrl' => $this->cover_url,
            'isRead' => $this->is_read,
            'createdAt' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
