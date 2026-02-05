<?php

namespace App\Http\Resources\App;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 视频流列表资源类 - 用于刷视频场景
 */
class VideoFeedResource extends JsonResource
{
    const DEFAULT_AVATAR = '';
    const DEFAULT_NICKNAME = '用户';

    /**
     * 已收藏的帖子ID数组
     */
    protected static $collectedPostIds = [];

    /**
     * 已点赞的帖子ID数组
     */
    protected static $likedPostIds = [];

    /**
     * 已关注的用户ID数组
     */
    protected static $followedMemberIds = [];

    /**
     * 设置已收藏的帖子ID
     */
    public static function setCollectedPostIds(array $ids): void
    {
        self::$collectedPostIds = $ids;
    }

    /**
     * 设置已点赞的帖子ID
     */
    public static function setLikedPostIds(array $ids): void
    {
        self::$likedPostIds = $ids;
    }

    /**
     * 设置已关注的用户ID
     */
    public static function setFollowedMemberIds(array $ids): void
    {
        self::$followedMemberIds = $ids;
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            // 基础信息
            'postId' => $this->post_id,
            'title' => $this->title ?? '',
            'content' => $this->content ?? '',

            // 视频信息
            'video' => $this->formatVideo(),
            'cover' => $this->formatCover(),

            // 统计数据
            'viewCount' => $this->stat ? $this->stat->view_count : 0,
            'likeCount' => $this->stat ? $this->stat->like_count : 0,
            'commentCount' => $this->stat ? $this->stat->comment_count : 0,
            'shareCount' => $this->stat ? $this->stat->share_count : 0,
            'collectionCount' => $this->stat ? $this->stat->collection_count : 0,

            // 交互状态
            'isLiked' => in_array($this->post_id, self::$likedPostIds),
            'isCollected' => in_array($this->post_id, self::$collectedPostIds),

            // 作者信息
            'author' => $this->formatAuthor(),

            // 时间
            'createdAt' => $this->created_at ? $this->created_at->toIso8601String() : null,
        ];
    }

    /**
     * 格式化视频信息
     */
    protected function formatVideo(): array
    {
        $mediaData = $this->media_data ?? [];
        $video = isset($mediaData[0]) ? $mediaData[0] : [];

        return [
            'url' => $video['url'] ?? '',
            'width' => $video['width'] ?? 0,
            'height' => $video['height'] ?? 0,
            'duration' => $video['duration'] ?? 0,
        ];
    }

    /**
     * 格式化封面信息
     */
    protected function formatCover(): array
    {
        $cover = $this->cover ?? [];

        return [
            'url' => $cover['url'] ?? '',
            'width' => $cover['width'] ?? 0,
            'height' => $cover['height'] ?? 0,
        ];
    }

    /**
     * 格式化作者信息
     */
    protected function formatAuthor(): array
    {
        $member = $this->member;
        $memberId = $member ? $member->member_id : $this->member_id;

        if (!$member) {
            return [
                'memberId' => $this->member_id,
                'nickname' => self::DEFAULT_NICKNAME,
                'avatar' => self::DEFAULT_AVATAR,
                'isFollowed' => false,
            ];
        }

        return [
            'memberId' => $member->member_id,
            'nickname' => $member->nickname ?? self::DEFAULT_NICKNAME,
            'avatar' => $member->avatar ?? self::DEFAULT_AVATAR,
            'isFollowed' => in_array($memberId, self::$followedMemberIds),
        ];
    }
}
