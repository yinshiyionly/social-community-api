<?php

namespace App\Http\Resources\App;

use Carbon\Carbon;
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
            'id' => $this->sender_id,
            'nickname' => $sender ? $sender->nickname : '',
            'avatar' => $sender ? $sender->avatar : '',
            'title' => '关注了我',
            'content' => '',
            'time' => $this->created_at ? $this->formatRelativeTime($this->created_at) : '',
            'actionText' => $isFollowed ? '互相关注' : '回关',
            'read' => $this->is_read === 1,
        ];
    }

    /**
     * 格式化相对时间
     *
     * @param Carbon $time
     * @return string
     */
    protected function formatRelativeTime(Carbon $time): string
    {
        $now = Carbon::now();
        $diffInSeconds = $now->diffInSeconds($time);

        if ($diffInSeconds < 60) {
            return '刚刚';
        }

        $diffInMinutes = $now->diffInMinutes($time);
        if ($diffInMinutes < 60) {
            return $diffInMinutes . '分钟前';
        }

        $diffInHours = $now->diffInHours($time);
        if ($diffInHours < 24) {
            return $diffInHours . '小时前';
        }

        $diffInDays = $now->diffInDays($time);
        if ($diffInDays < 30) {
            return $diffInDays . '天前';
        }

        return $time->format('Y-m-d');
    }
}
