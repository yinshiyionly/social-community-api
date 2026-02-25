<?php

namespace App\Http\Resources\App;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 小秘书消息列表资源类
 */
class MessageSecretaryResource extends JsonResource
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
            'id' => $this->message_id,
            'title' => $this->title ?: '',
            'content' => $this->getContentSummary(),
            'time' => $this->created_at ? $this->formatRelativeTime($this->created_at) : '',
            'read' => $this->is_read === 1,
        ];
    }

    /**
     * 获取内容摘要
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
