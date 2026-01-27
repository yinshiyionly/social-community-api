<?php

namespace App\Http\Resources\App;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 关注用户帖子列表资源类 - 用于关注动态流展示
 */
class FollowPostListResource extends JsonResource
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
        $member = $this->member;

        return [
            'postId' => $this->post_id,
            'title' => $this->title ?? '',
            'content' => $this->content ?? '',
            'cover' => $this->extractCover($firstMedia),
            'isVideo' => $this->isVideoPost($firstMedia),
            'likeCount' => $this->like_count ?? 0,
            'commentCount' => $this->comment_count ?? 0,
            'createdAt' => $this->created_at
                ? $this->created_at->format('Y-m-d H:i:s')
                : null,
            'author' => [
                'memberId' => $member ? $member->member_id : null,
                'nickname' => $member ? ($member->nickname ?? '') : '',
                'avatar' => $member ? ($member->avatar ?? '') : '',
            ],
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

    /**
     * 判断是否为视频帖子
     *
     * @param array|null $media 媒体数据
     * @return bool
     */
    protected function isVideoPost($media): bool
    {
        if (!$media || !is_array($media)) {
            return false;
        }
        $type = isset($media['type']) ? $media['type'] : 'image';
        return $type === 'video';
    }
}
