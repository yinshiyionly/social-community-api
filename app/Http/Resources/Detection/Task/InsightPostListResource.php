<?php

declare(strict_types=1);

namespace App\Http\Resources\Detection\Task;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 洞察数据列表资源
 */
class InsightPostListResource extends JsonResource
{
    /**
     * 主域名标签映射
     */
    private const MAIN_DOMAIN_LABELS = [
        'douyin.com' => '抖音',
        'toutiao.com' => '头条',
        'ixigua.com' => '西瓜视频',
    ];

    private const POST_TYPE_LABELS = [
        1 => '图文',
        2 => '横版短视频',
        3 => '竖版短视频',
    ];

    private const SENTIMENT_LABELS = [
        0 => '中立',
        1 => '正向',
        2 => '负向'
    ];

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'origin_id' => $this->origin_id,
            'post_id' => $this->post_id,
            'title' => $this->title,
            'url' => $this->url,
            'main_domain' => $this->main_domain,
            'main_domain_label' => self::MAIN_DOMAIN_LABELS[$this->main_domain] ?? $this->main_domain,
            'domain' => $this->domain,
            'publish_time' => !empty($this->publish_time) ? $this->publish_time->format('Y-m-d H:i:s') : '',
            'post_type' => $this->post_type ?? 1,
            'post_type_label' => self::POST_TYPE_LABELS[$this->post_type ?? 1],
            // 'status' => $this->status,
            'process_state' => $this->process_state,
            'process_state_label' => $this->process_state_label,
            'sentiment' => $this->sentiment ?? 0,
            'sentiment_label' => self::SENTIMENT_LABELS[$this->sentiment ?? 0],
            // 'feature' => $this->feature,
            // todo 隐藏 feature 字段, 拆分成 ocr、asr 和 tags
            'ocr' => $this->feature['ocr'] ?? '',
            'asr' => $this->feature['asr'] ?? '',
            'tags' => $this->feature['tags'] ?? [],
            // 'poi' => $this->poi,
            // 'video_info' => $this->video_info,
            'based_location' => $this->formatBasedLocation($this->based_location ?? []),
            'matched_task_ids' => $this->matched_task_ids,
        ];
    }

    protected function formatBasedLocation($basedLocation)
    {
        if (isset($basedLocation['public_location']['region']) && isset($basedLocation['public_location']['province'])) {
            return sprintf("%s-%s", $basedLocation['public_location']['region'], $basedLocation['public_location']['province']);
        }
        return $basedLocation['public_location']['location'];
    }
}
