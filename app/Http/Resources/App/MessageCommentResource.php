<?php

namespace App\Http\Resources\App;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 评论消息列表资源类
 */
class MessageCommentResource extends JsonResource
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
        $post = $this->post;

        return [
            'id'         => $this->target_id,
            'nickname'   => $sender ? $sender->nickname : '',
            'avatar'     => $sender ? $sender->avatar : '',
            'title'      => '评论了我的帖子',
            'content'    => $this->content_summary ?: '',
            'postCover'  => $this->cover_url,
            // 后续在 app_message_interaction 数据表冗余 post_type 字段
            'postType'   => $post['post_type'] ?? 1,
            'time'       => $this->created_at ? $this->formatRelativeTime($this->created_at) : '',
            'actionText' => '回复',
            'read'       => $this->is_read === 1,
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

        // 今天：显示 HH:mm
        if ($time->isToday()) {
            return $time->format('H:i');
        }

        // 昨天
        if ($time->isYesterday()) {
            return '昨天 ' . $time->format('H:i');
        }

        $diffInDays = $now->diffInDays($time);

        // 一周内：显示 X天前
        if ($diffInDays < 7) {
            return $diffInDays . '天前';
        }

        // 今年内：显示 MM-DD
        if ($time->year === $now->year) {
            return $time->format('m-d');
        }

        // 更早：显示 YYYY-MM-DD
        return $time->format('Y-m-d');
    }
}
