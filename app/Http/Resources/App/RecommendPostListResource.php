<?php

namespace App\Http\Resources\App;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 推荐帖子列表资源类 - 用于猜你喜欢展示
 */
class RecommendPostListResource extends JsonResource
{
    /**
     * 点赞的帖子ID数组
     *
     * @var array
     */
    protected static $likedIds = [];

    /**
     * 收藏的帖子ID数组
     *
     * @var array
     */
    protected static $collectedIds = [];

    /**
     * 关注的作者ID数组
     *
     * @var array
     */
    protected static $followedIds = [];

    /**
     * 设置交互状态数据
     *
     * @param array $likedIds
     * @param array $collectedIds
     * @param array $followedIds
     */
    public static function setInteractionData(array $likedIds, array $collectedIds, array $followedIds): void
    {
        self::$likedIds = $likedIds;
        self::$collectedIds = $collectedIds;
        self::$followedIds = $followedIds;
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
        $member = $this->member;
        $postType = $this->post_type;

        $data = [
            'id' => $this->post_id,
            'postType' => $postType,
            'author' => [
                'id' => $member ? $member->member_id : null,
                'avatar' => $member ? ($member->avatar ?? '') : '',
                'nickname' => $member ? ($member->nickname ?? '') : '',
            ],
            'content' => $this->content ?? '',
            'commentCount' => $this->stat ? $this->stat->comment_count : 0,
            'favoriteCount' => $this->stat ? $this->stat->collection_count : 0,
            'likeCount' => $this->stat ? $this->stat->like_count : 0,
            'isFollowed' => $member ? in_array($member->member_id, self::$followedIds) : false,
            'isLiked' => in_array($this->post_id, self::$likedIds),
            'isFavorited' => in_array($this->post_id, self::$collectedIds),
            'createTime' => $this->created_at ? $this->created_at->toISOString() : null,
        ];

        // 视频动态使用 videoCover，图文/文章动态使用 images
        if ($postType === 2) {
            // 视频动态
            $cover = $this->cover ?? [];
            $data['videoCover'] = isset($cover['url']) ? $cover['url'] : '';
        } else {
            // 图文/文章动态
            $data['images'] = $this->extractImages($mediaData);
        }

        return $data;
    }

    /**
     * 从媒体数据中提取图片URL列表
     *
     * @param array $mediaData
     * @return array
     */
    protected function extractImages(array $mediaData): array
    {
        $images = [];
        foreach ($mediaData as $media) {
            if (isset($media['url'])) {
                $images[] = $media['url'];
            }
        }
        return $images;
    }
}
