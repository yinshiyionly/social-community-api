<?php

namespace App\Http\Resources\App;

use App\Constant\MessageType;
use App\Helper\DatetimeHelper;
use Carbon\Carbon;
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
        $post = $this->post;

        // 构建操作描述
        $title = $this->message_type === MessageType::LIKE ? '点了赞' : '收藏了';

        // 帖子标题
        $postTitle = $post ? $post->title : '';

        // 帖子封面
        $postCover = null;
        if ($post && !empty($post->cover) && isset($post->cover['url'])) {
            $postCover = $post->cover['url'];
        }

        return [
            'id' => $this->target_id,
            'nickname' => $sender ? $sender->nickname : '',
            'avatar' => $sender ? $sender->avatar : '',
            'title' => $title,
            'content' => $postTitle,
            'postCover' => $postCover,
            'time' => $this->created_at ? $this->formatRelativeTime($this->created_at) : '',
            'actionText' => '查看',
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
