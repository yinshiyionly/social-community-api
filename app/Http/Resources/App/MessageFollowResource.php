<?php

namespace App\Http\Resources\App;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 关注消息列表资源类
 */
class MessageFollowResource extends JsonResource
{
    /**
     * 已关注的会员ID列表（用于判断回关状态）
     *
     * @var array
     */
    protected static $followedMemberIds = [];

    /**
     * 设置已关注的会员ID列表
     *
     * @param array $memberIds
     * @return void
     */
    public static function setFollowedMemberIds(array $memberIds): void
    {
        self::$followedMemberIds = $memberIds;
    }

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        $sender = $this->sender;
        $isFollowed = false;

        if ($sender) {
            $isFollowed = in_array($sender->member_id, self::$followedMemberIds);
        }

        return [
            'messageId' => $this->message_id,
            'sender' => $sender ? [
                'memberId' => $sender->member_id,
                'nickname' => $sender->nickname,
                'avatar' => $sender->avatar,
                'isFollowed' => $isFollowed,
            ] : null,
            'isRead' => $this->is_read,
            'createdAt' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
