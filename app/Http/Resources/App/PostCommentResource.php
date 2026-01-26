<?php

namespace App\Http\Resources\App;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 帖子评论资源类 - 用于一级评论展示
 */
class PostCommentResource extends JsonResource
{
    const DEFAULT_AVATAR = '';
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
            'commentId' => $this->comment_id,
            'content' => $this->content,
            'likeCount' => $this->like_count ?? 0,
            'replyCount' => $this->reply_count ?? 0,
            'createdAt' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'author' => $this->formatAuthor(),
            'replies' => $this->formatReplies(),
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
            return (new PostCommentReplyResource($reply))->resolve();
        })->toArray();
    }
}
