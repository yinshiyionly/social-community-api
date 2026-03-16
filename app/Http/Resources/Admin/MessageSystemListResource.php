<?php

namespace App\Http\Resources\Admin;

use App\Constant\MessageType;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 后台系统消息列表资源。
 */
class MessageSystemListResource extends JsonResource
{
    /**
     * 输出后台系统消息列表项。
     *
     * 字段约定：
     * 1. 字段命名统一使用 camelCase；
     * 2. sender/receiver 为前端直接可渲染结构，避免前端再次拼装；
     * 3. createdAt 固定输出 Y-m-d H:i:s，便于后台表格统一展示。
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'messageId' => (int) $this->message_id,
            'sender' => [
                'memberId' => $this->sender ? (int) $this->sender->member_id : $this->normalizeNullableInt($this->sender_id),
                'nickname' => $this->sender ? (string) ($this->sender->nickname ?? '') : '',
                'avatar' => $this->sender ? (string) ($this->sender->avatar ?? '') : '',
                'officialLabel' => $this->sender ? (string) ($this->sender->official_label ?? '') : '',
            ],
            'receiver' => [
                'memberId' => $this->receiver ? (int) $this->receiver->member_id : $this->normalizeNullableInt($this->receiver_id),
                'nickname' => $this->receiver ? (string) ($this->receiver->nickname ?? '') : '',
                'avatar' => $this->receiver ? (string) ($this->receiver->avatar ?? '') : '',
            ],
            'isBroadcast' => $this->receiver_id === null ? 1 : 0,
            'title' => (string) ($this->title ?? ''),
            'content' => (string) ($this->content ?? ''),
            'coverUrl' => (string) ($this->cover_url ?? ''),
            'linkType' => $this->normalizeNullableInt($this->link_type),
            'linkTypeName' => $this->link_type ? MessageType::getLinkTypeName((int) $this->link_type) : null,
            'linkUrl' => (string) ($this->link_url ?? ''),
            'isRead' => (int) ($this->is_read ?? 0),
            'createdAt' => $this->formatDateTime($this->created_at),
        ];
    }

    /**
     * 规范化可空整数。
     *
     * @param mixed $value
     * @return int|null
     */
    protected function normalizeNullableInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    /**
     * 格式化时间字段为统一字符串。
     *
     * @param mixed $value
     * @return string|null
     */
    protected function formatDateTime($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        $timestamp = strtotime((string) $value);

        return $timestamp === false ? (string) $value : date('Y-m-d H:i:s', $timestamp);
    }
}
