<?php

namespace App\Http\Resources\App;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 帖子评论资源类 V2 - 用于新版评论列表接口
 */
class PostCommentV2Resource extends JsonResource
{
    const DEFAULT_AVATAR = '';
    const DEFAULT_NICKNAME = '用户';

    /**
     * 已点赞的评论ID列表
     *
     * @var array
     */
    protected static $likedCommentIds = [];

    /**
     * 设置已点赞的评论ID列表
     *
     * @param array $likedCommentIds
     * @return void
     */
    public static function setLikedCommentIds(array $likedCommentIds): void
    {
        self::$likedCommentIds = $likedCommentIds;
    }

    /**
     * 转换资源为数组
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        $member = $this->member;

        return [
            // 'id' => $this->post_id . '_comment_' . $this->comment_id,
            'id' => $this->comment_id ?? 0,
            'postId' => (string)$this->post_id,
            'memberId' => $this->member_id,
//            'parentCommentId' => $this->parent_id > 0
//                ? ($this->post_id . '_comment_' . $this->parent_id)
//                : null,
            'parentCommentId' => $this->parent_id > 0 ? $this->parent_id : null,
            'nickname' => $member ? ($member->nickname ?? self::DEFAULT_NICKNAME) : self::DEFAULT_NICKNAME,
            'avatar' => $member ? ($member->avatar ?? self::DEFAULT_AVATAR) : self::DEFAULT_AVATAR,
            'content' => $this->content,
            'createTime' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'likeCount' => $this->like_count ?? 0,
            'isLiked' => in_array($this->comment_id, self::$likedCommentIds),
            'replies' => $this->formatReplies(),
        ];
    }

    /**
     * 格式化回复列表（预览，最多3条）
     *
     * @return array
     */
    protected function formatReplies(): array
    {
        if (!$this->relationLoaded('replies')) {
            return [];
        }

        $replies = $this->replies;
        if (!$replies || $replies->isEmpty()) {
            return [];
        }

        return $replies->map(function ($reply) {
            $member = $reply->member;

            return [
                // 'id' => $reply->post_id . '_comment_' . $reply->comment_id,
                'id' => $reply->comment_id ?? 0,
                'postId' => (string)$reply->post_id,
                'memberId' => $reply->member_id,
                // 'parentCommentId' => $reply->post_id . '_comment_' . $reply->parent_id,
                'parentCommentId' => $reply->parent_id,
                'nickname' => $member ? ($member->nickname ?? self::DEFAULT_NICKNAME) : self::DEFAULT_NICKNAME,
                'avatar' => $member ? ($member->avatar ?? self::DEFAULT_AVATAR) : self::DEFAULT_AVATAR,
                'content' => $reply->content,
                'createTime' => $reply->created_at ? $reply->created_at->toIso8601String() : null,
                'likeCount' => $reply->like_count ?? 0,
                'isLiked' => in_array($reply->comment_id, self::$likedCommentIds),
            ];
        })->toArray();
    }
}
