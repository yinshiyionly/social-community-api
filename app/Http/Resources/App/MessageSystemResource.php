<?php

namespace App\Http\Resources\App;

use App\Constant\MessageType;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 系统消息列表资源类
 */
class MessageSystemResource extends JsonResource
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
            'content' => $this->getContentSummary(),
            'coverUrl' => $this->cover_url,
            'linkType' => $this->link_type,
            'linkTypeName' => $this->link_type ? MessageType::getLinkTypeName($this->link_type) : null,
            'linkUrl' => $this->link_url,
            'isRead' => $this->is_read,
            'createdAt' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
        ];
    }

    /**
     * 获取内容摘要（列表展示时截取前100字符）
     *
     * @return string
     */
    protected function getContentSummary(): string
    {
        $content = strip_tags($this->content);

        if (mb_strlen($content) > 100) {
            return mb_substr($content, 0, 100) . '...';
        }

        return $content;
    }
}
