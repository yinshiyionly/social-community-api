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
     * 当前登录用户ID（由控制器注入，0 表示游客态）。
     *
     * @var int
     */
    protected static $currentMemberId = 0;

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
     * 设置当前登录用户ID。
     *
     * @param int|null $memberId
     * @return void
     */
    public static function setCurrentMemberId(?int $memberId): void
    {
        self::$currentMemberId = $memberId ? (int)$memberId : 0;
    }

    /**
     * 输出视频流卡片数据。
     *
     * 字段约定：
     * - isFavorited 为收藏状态标准字段；
     * - isCollected 为历史兼容字段，与 isFavorited 同值。
     * - isOwned 表示该帖子是否由当前登录用户发布。
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        $isFavorited = in_array($this->post_id, self::$collectedPostIds);

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
            'isOwned' => self::$currentMemberId > 0 && (int)$this->member_id === self::$currentMemberId,
            'isLiked' => in_array($this->post_id, self::$likedPostIds),
            'isFavorited' => $isFavorited,
            // 兼容旧客户端字段，值与 isFavorited 保持一致。
            'isCollected' => $isFavorited,

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
