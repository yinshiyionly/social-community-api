<?php

namespace App\Http\Resources\App;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 帖子列表资源类 - 用于瀑布流列表展示
 */
class PostListResource extends JsonResource
{
    // 默认头像
    const DEFAULT_AVATAR = '';
    // 默认昵称
    const DEFAULT_NICKNAME = '用户';
    // 默认宽高比
    const DEFAULT_ASPECT_RATIO = 1.0;

    /**
     * 已收藏的帖子ID数组（由控制器注入）
     *
     * @var array
     */
    public static $collectedPostIds = [];

    /**
     * 设置已收藏的帖子ID
     *
     * @param array $postIds
     */
    public static function setCollectedPostIds(array $postIds): void
    {
        self::$collectedPostIds = $postIds;
    }

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
            'id' => $this->post_id,
            'cover' => $this->extractCover($firstMedia),
            'title' => $this->title ?? '',
            'avatar' => $this->getAuthorAvatar(),
            'nickname' => $this->getAuthorNickname(),
            'likes' => $this->like_count ?? 0,
            'isVideo' => $this->isVideoPost($firstMedia),
            'aspectRatio' => $this->calculateAspectRatio($firstMedia),
            'isCollected' => in_array($this->post_id, self::$collectedPostIds),
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
     * 获取作者头像
     *
     * @return string
     */
    protected function getAuthorAvatar(): string
    {
        $member = $this->member;
        if (!$member) {
            return self::DEFAULT_AVATAR;
        }
        return $member->avatar ?? self::DEFAULT_AVATAR;
    }

    /**
     * 获取作者昵称
     *
     * @return string
     */
    protected function getAuthorNickname(): string
    {
        $member = $this->member;
        if (!$member) {
            return self::DEFAULT_NICKNAME;
        }
        return $member->nickname ?? self::DEFAULT_NICKNAME;
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

    /**
     * 计算封面图宽高比
     *
     * @param array|null $media 媒体数据
     * @return float
     */
    protected function calculateAspectRatio($media): float
    {
        if (!$media || !is_array($media)) {
            return self::DEFAULT_ASPECT_RATIO;
        }
        $width = isset($media['width']) ? (int)$media['width'] : 0;
        $height = isset($media['height']) ? (int)$media['height'] : 0;
        if ($height <= 0) {
            return self::DEFAULT_ASPECT_RATIO;
        }
        return round($width / $height, 2);
    }
}
