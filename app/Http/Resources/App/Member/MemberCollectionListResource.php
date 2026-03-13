<?php

namespace App\Http\Resources\App\Member;

use App\Models\App\AppPostBase;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 用户收藏帖子列表资源类
 */
class MemberCollectionListResource extends JsonResource
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
     * - isLiked/isFavorited 表示“当前登录用户”视角下的互动状态；
     * - 当收藏记录关联帖子被删除时返回 null，由上层按既有规则过滤。
     *
     * @param \Illuminate\Http\Request $request
     * @return array|null
     */
    public function toArray($request)
    {
        $post = $this->post;

        // 帖子不存在或已删除
        if (!$post) {
            return null;
        }

        $cover = $post->cover;
        $author = $post->member;
        $isFavorited = in_array($post->post_id, self::$favoritedPostIds);

        return [
            'id' => $post->post_id,
            'cover' => isset($cover['url']) ? $cover['url'] : '',
            'title' => $post->title ?: ($post->content ?: ''),
            'avatar' => $author ? ($author->avatar ?? '') : '',
            'nickname' => $author ? ($author->nickname ?? '') : '',
            'views' => $post->stat ? $post->stat->view_count : 0,
            'likes' => $post->stat ? $post->stat->like_count : 0,
            'postType' => $post->post_type,
            'isVideo' => $post->post_type == AppPostBase::POST_TYPE_VIDEO,
            'aspectRatio' => $this->calculateAspectRatio($cover),
            'isLiked' => in_array($post->post_id, self::$likedPostIds),
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
