<?php

namespace App\Http\Resources\App;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 帖子详情资源类 - 用于详情页展示
 */
class PostResource extends JsonResource
{
    // 默认头像
    const DEFAULT_AVATAR = '';
    // 默认昵称
    const DEFAULT_NICKNAME = '用户';

    /**
     * 转换资源为数组
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            // 基础信息
            'postId' => $this->post_id,
            'postType' => $this->post_type,
            'title' => $this->title ?? '',
            'content' => $this->content ?? '',
            'mediaData' => $this->media_data ?? [],

            // 位置信息
            'locationName' => $this->location_name,
            'locationGeo' => $this->location_geo,

            // 统计数据
            'viewCount' => $this->stat ? $this->stat->view_count : 0,
            'likeCount' => $this->stat ? $this->stat->like_count : 0,
            'commentCount' => $this->stat ? $this->stat->comment_count : 0,
            'shareCount' => $this->stat ? $this->stat->share_count : 0,
            'collectionCount' => $this->stat ? $this->stat->collection_count : 0,

            // 状态
            'isTop' => $this->is_top ?? 0,

            // 作者信息
            'author' => $this->formatAuthor(),

            // 时间
            'createdAt' => $this->created_at ? $this->created_at->toIso8601String() : null,
        ];
    }

    /**
     * 格式化作者信息
     *
     * @return array
     */
    protected function formatAuthor(): array
    {
        $member = $this->member;

        if (!$member) {
            return [
                'memberId' => $this->member_id,
                'nickname' => self::DEFAULT_NICKNAME,
                'avatar' => self::DEFAULT_AVATAR,
            ];
        }

        return [
            'memberId' => $member->member_id,
            'nickname' => $member->nickname ?? self::DEFAULT_NICKNAME,
            'avatar' => $member->avatar ?? self::DEFAULT_AVATAR,
        ];
    }
}
