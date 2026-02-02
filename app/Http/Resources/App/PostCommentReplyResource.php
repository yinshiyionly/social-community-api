<?php

namespace App\Http\Resources\App;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 帖子评论回复资源类 - 用于二级评论展示
 */
class PostCommentReplyResource extends JsonResource
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
        return [
            'commentId' => $this->comment_id,
            'content' => $this->content,
            'likeCount' => $this->like_count ?? 0,
            'isLiked' => in_array($this->comment_id, self::$likedCommentIds),
            'createdAt' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'author' => $this->formatAuthor(),
            'replyTo' => $this->formatReplyTo(),
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

    /**
     * 格式化被回复用户信息
     *
     * @return array|null
     */
    protected function formatReplyTo()
    {
        if ($this->reply_to_member_id <= 0) {
            return null;
        }

        $replyToMember = $this->replyToMember;

        if (!$replyToMember) {
            return [
                'memberId' => $this->reply_to_member_id,
                'nickname' => self::DEFAULT_NICKNAME,
            ];
        }

        return [
            'memberId' => $replyToMember->member_id,
            'nickname' => $replyToMember->nickname ?? self::DEFAULT_NICKNAME,
        ];
    }
}
