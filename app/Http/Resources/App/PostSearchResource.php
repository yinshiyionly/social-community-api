<?php

namespace App\Http\Resources\App;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 帖子搜索资源类 - 用于搜索结果展示
 */
class PostSearchResource extends JsonResource
{
    /**
     * 转换资源为数组
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        $mediaData = $this->media_data ?? [];
        $firstMedia = is_array($mediaData) && count($mediaData) > 0 ? $mediaData[0] : null;

        return [
            'postId' => $this->post_id,
            'cover' => $this->extractCover($firstMedia),
            'title' => $this->title ?? '',
            'likeCount' => $this->like_count ?? 0,
        ];
    }

    /**
     * 从媒体数据中提取封面图
     *
     * @param array|null $media 媒体数据
     * @return string
     */
    protected function extractCover($media): string
    {
        if (!$media || !is_array($media)) {
            return '';
        }
        // 视频取封面图，图片取原图
        if (isset($media['cover']) && !empty($media['cover'])) {
            return $media['cover'];
        }
        return isset($media['url']) ? $media['url'] : '';
    }
}
