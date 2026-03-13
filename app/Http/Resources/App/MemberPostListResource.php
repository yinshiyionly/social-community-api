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
     * 当前登录用户已点赞的帖子ID列表（由控制器批量注入）。
     *
     * @var array
     */
    protected static $likedPostIds = [];

    /**
     * 当前登录用户已收藏的帖子ID列表（由控制器批量注入）。
     *
     * @var array
     */
    protected static $favoritedPostIds = [];

    /**
     * 设置帖子互动状态映射数据。
     *
     * @param array $likedPostIds
     * @param array $favoritedPostIds
     * @return void
     */
    public static function setInteractionData(array $likedPostIds, array $favoritedPostIds): void
    {
        self::$likedPostIds = $likedPostIds;
        self::$favoritedPostIds = $favoritedPostIds;
    }

    /**
     * 转换资源为数组
     *
     * 字段约定：
     * - isLiked/isFavorited 表示“当前登录用户”对该帖子的互动状态；
     * - 未命中注入列表时默认 false，避免资源层触发额外查询。
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        $cover = $this->cover;
        $isFavorited = in_array($this->post_id, self::$favoritedPostIds);

        return [
            'id' => $this->post_id,
            'cover' => isset($cover['url']) ? $cover['url'] : '',
            'title' => $this->title ?: ($this->content ?: ''),
            'avatar' => $this->member ? $this->member->avatar : '',
            'nickname' => $this->member ? $this->member->nickname : '',
            'likes' => $this->stat ? $this->stat->like_count : 0,
            'views' => $this->stat ? $this->stat->view_count : 0,
            'postType' => $this->post_type,
            'isVideo' => $this->post_type == AppPostBase::POST_TYPE_VIDEO,
            'aspectRatio' => $this->calculateAspectRatio($cover),
            'isLiked' => in_array($this->post_id, self::$likedPostIds),
            'isFavorited' => $isFavorited,
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
