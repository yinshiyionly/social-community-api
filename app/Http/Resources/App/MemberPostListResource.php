<?php

namespace App\Http\Resources\App;

use App\Models\App\AppPostBase;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 用户帖子列表资源类
 */
class MemberPostListResource extends JsonResource
{
    /**
     * 转换资源为数组
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        $cover = $this->cover;

        return [
            'id' => $this->post_id,
            'cover' => isset($cover['url']) ? $cover['url'] : '',
            'title' => $this->title ?: ($this->content ?: ''),
            'likes' => $this->stat ? $this->stat->like_count : 0,
            'isVideo' => $this->post_type == AppPostBase::POST_TYPE_VIDEO,
            'aspectRatio' => $this->calculateAspectRatio($cover),
        ];
    }

    /**
     * 计算封面图宽高比
     *
     * @param array|null $cover 封面数据
     * @return float
     */
    protected function calculateAspectRatio($cover): float
    {
        if (!$cover || !is_array($cover)) {
            return 1.0;
        }

        $width = isset($cover['width']) ? (int)$cover['width'] : 0;
        $height = isset($cover['height']) ? (int)$cover['height'] : 0;

        if ($height <= 0) {
            return 1.0;
        }

        return round($width / $height, 2);
    }
}
