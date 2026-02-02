<?php

namespace App\Services\App;

use App\Constant\MessageType;
use App\Models\App\AppPostCommentLike;
use App\Models\App\AppPostBase;
use App\Models\App\AppPostComment;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PostCommentService
{
    /**
     * 获取帖子评论列表（游标分页）
     *
     * @param int $postId 帖子ID
     * @param int|null $memberId 当前登录会员ID
     * @param string|null $cursor 游标
     * @param int $pageSize 每页数量
     * @return array ['paginator' => CursorPaginator, 'likedCommentIds' => array]
     */
    public function getCommentList(int $postId, ?int $memberId = null, ?string $cursor = null, int $pageSize = 10): array
    {
        $paginator = AppPostComment::query()
            ->with([
                'member',
                'replies' => function ($query) {
                    $query->with(['member', 'replyToMember'])
                        ->normal()
                        ->orderBy('created_at')
                        ->limit(3);
                }
            ])
            ->byPost($postId)
            ->topLevel()
            ->normal()
            ->orderByDesc('comment_id')
            ->cursorPaginate($pageSize, ['*'], 'cursor', $cursor);

        // 获取当前用户点赞的评论ID列表
        $likedCommentIds = [];
        if ($memberId) {
            $commentIds = $this->extractCommentIds($paginator);
            $likedCommentIds = AppPostCommentLike::getLikedCommentIds($memberId, $commentIds);
        }

        return [
            'paginator' => $paginator,
            'likedCommentIds' => $likedCommentIds,
        ];
    }

    /**
     * 从分页结果中提取所有评论ID（包括回复）
     *
     * @param CursorPaginator $paginator
     * @return array
     */
    private function extractCommentIds(CursorPaginator $paginator): array
    {
        $commentIds = [];
        foreach ($paginator->items() as $comment) {
            $commentIds[] = $comment->comment_id;
            if ($comment->relationLoaded('replies')) {
                foreach ($comment->replies as $reply) {
                    $commentIds[] = $reply->comment_id;
                }
            }
        }
        return $commentIds;
    }

    /**
     * 获取评论的回复列表（游标分页）
     *
     * @param int $commentId 父评论ID
     * @param int|null $memberId 当前登录会员ID
     * @param string|null $cursor 游标
     * @param int $pageSize 每页数量
     * @return array ['paginator' => CursorPaginator, 'likedCommentIds' => array]
     */
    public function getReplyList(int $commentId, ?int $memberId = null, ?string $cursor = null, int $pageSize = 10): array
    {
        $paginator = AppPostComment::query()
            ->with(['member', 'replyToMember'])
            ->byParent($commentId)
            ->normal()
            ->orderBy('created_at')
            ->cursorPaginate($pageSize, ['*'], 'cursor', $cursor);

        // 获取当前用户点赞的评论ID列表
        $likedCommentIds = [];
        if ($memberId) {
            $commentIds = $paginator->pluck('comment_id')->toArray();
            $likedCommentIds = AppPostCommentLike::getLikedCommentIds($memberId, $commentIds);
        }

        return [
            'paginator' => $paginator,
            'likedCommentIds' => $likedCommentIds,
        ];
    }

    /**
     * 发表评论
     *
     * @param int $memberId 会员ID
     * @param int $postId 帖子ID
     * @param string $content 评论内容
     * @param int $parentId 父评论ID（0表示一级评论）
     * @param int $replyToMemberId 回复目标用户ID
     * @return array
     */
    public function createComment(
        int    $memberId,
        int    $postId,
        string $content,
        int    $parentId = 0,
        int    $replyToMemberId = 0
    ): array
    {
        // 检查帖子是否存在且可访问
        $post = AppPostBase::query()
            ->select(['post_id', 'member_id', 'cover'])
            ->approved()
            ->visible()
            ->where('post_id', $postId)
            ->first();

        if (!$post) {
            return [
                'success' => false,
                'message' => 'post_not_found',
                'comment' => null,
            ];
        }

        // 如果是回复，检查父评论是否存在
        $parentComment = null;
        if ($parentId > 0) {
            $parentComment = AppPostComment::query()
                ->normal()
                ->where('comment_id', $parentId)
                ->where('post_id', $postId)
                ->first();

            if (!$parentComment) {
                return [
                    'success' => false,
                    'message' => 'parent_not_found',
                    'comment' => null,
                ];
            }

            // 如果父评论本身是回复，则将 parent_id 指向一级评论
            if (!$parentComment->isTopLevel()) {
                $parentId = $parentComment->parent_id;
            }
        }

        try {
            DB::beginTransaction();

            // 创建评论
            $comment = AppPostComment::create([
                'post_id' => $postId,
                'member_id' => $memberId,
                'parent_id' => $parentId,
                'reply_to_member_id' => $replyToMemberId,
                'content' => $content,
                'status' => AppPostComment::STATUS_NORMAL,
            ]);

            // 增加帖子评论数
            $post->getOrCreateStat()->incrementCommentCount();

            // 如果是回复，增加父评论的回复数
            if ($parentId > 0) {
                $topComment = AppPostComment::find($parentId);
                if ($topComment) {
                    $topComment->incrementReplyCount();
                }
            }

            DB::commit();

            // 加载关联数据
            $comment->load(['member', 'replyToMember']);

            // 创建评论消息
            $coverUrl = isset($post->cover['url']) ? $post->cover['url'] : null;
            if ($parentId > 0 && $replyToMemberId > 0) {
                // 回复评论，通知被回复的用户
                MessageService::createCommentMessage(
                    $memberId,
                    $replyToMemberId,
                    $comment->comment_id,
                    MessageType::TARGET_COMMENT,
                    mb_substr($content, 0, 50),
                    $coverUrl
                );
            } else {
                // 一级评论，通知帖子作者
                MessageService::createCommentMessage(
                    $memberId,
                    $post->member_id,
                    $postId,
                    MessageType::TARGET_POST,
                    mb_substr($content, 0, 50),
                    $coverUrl
                );
            }

            return [
                'success' => true,
                'message' => 'created',
                'comment' => $comment,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 删除评论
     *
     * @param int $memberId 会员ID
     * @param int $commentId 评论ID
     * @return array
     */
    public function deleteComment(int $memberId, int $commentId): array
    {
        $comment = AppPostComment::query()
            ->normal()
            ->where('comment_id', $commentId)
            ->first();

        if (!$comment) {
            return [
                'success' => false,
                'message' => 'not_found',
            ];
        }

        // 只能删除自己的评论
        if ($comment->member_id !== $memberId) {
            return [
                'success' => false,
                'message' => 'forbidden',
            ];
        }

        try {
            DB::beginTransaction();

            // 软删除评论
            $comment->status = AppPostComment::STATUS_DELETED;
            $comment->save();
            $comment->delete();

            // 减少帖子评论数
            $post = AppPostBase::find($comment->post_id);
            if ($post) {
                $post->getOrCreateStat()->decrementCommentCount();
            }

            // 如果是回复，减少父评论的回复数
            if ($comment->parent_id > 0) {
                $parentComment = AppPostComment::find($comment->parent_id);
                if ($parentComment) {
                    $parentComment->decrementReplyCount();
                }
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'deleted',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 获取评论详情
     *
     * @param int $commentId 评论ID
     * @return AppPostComment|null
     */
    public function getCommentDetail(int $commentId)
    {
        return AppPostComment::query()
            ->with(['member', 'replyToMember'])
            ->normal()
            ->where('comment_id', $commentId)
            ->first();
    }

    /**
     * 点赞评论
     *
     * @param int $memberId 会员ID
     * @param int $commentId 评论ID
     * @return array
     * @throws \Exception
     */
    public function likeComment(int $memberId, int $commentId): array
    {
        // 检查评论是否存在
        $comment = AppPostComment::query()
            ->select(['comment_id', 'post_id', 'member_id', 'content', 'like_count'])
            ->normal()
            ->where('comment_id', $commentId)
            ->first();

        if (!$comment) {
            return [
                'success' => false,
                'message' => 'comment_not_found',
            ];
        }

        // 检查是否已点赞
        if (AppPostCommentLike::isLiked($memberId, $commentId)) {
            return [
                'success' => false,
                'message' => 'already_liked',
            ];
        }

        try {
            DB::beginTransaction();

            // 创建点赞记录
            AppPostCommentLike::create([
                'member_id' => $memberId,
                'comment_id' => $commentId,
            ]);

            // 增加评论点赞数
            $comment->incrementLikeCount();

            DB::commit();

            // 异步发送消息通知（通知评论作者）
            $this->sendCommentLikeMessage($memberId, $comment);

            return [
                'success' => true,
                'message' => 'liked',
                'likeCount' => $comment->like_count
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 取消点赞评论
     *
     * @param int $memberId 会员ID
     * @param int $commentId 评论ID
     * @return array
     * @throws \Exception
     */
    public function unlikeComment(int $memberId, int $commentId): array
    {
        // 检查评论是否存在
        $comment = AppPostComment::query()
            ->select(['comment_id', 'like_count'])
            ->normal()
            ->where('comment_id', $commentId)
            ->first();

        if (!$comment) {
            return [
                'success' => false,
                'message' => 'comment_not_found',
            ];
        }

        // 检查是否已点赞
        $like = AppPostCommentLike::byMember($memberId)
            ->byComment($commentId)
            ->first();

        if (!$like) {
            return [
                'success' => false,
                'message' => 'not_liked',
            ];
        }

        try {
            DB::beginTransaction();

            // 删除点赞记录
            $like->delete();

            // 减少评论点赞数
            $comment->decrementLikeCount();

            DB::commit();

            return [
                'success' => true,
                'message' => 'unliked',
                'likeCount' => max(0, $comment->like_count),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 发送评论点赞消息通知
     *
     * @param int $senderId 点赞者ID
     * @param AppPostComment $comment 评论
     * @return void
     */
    private function sendCommentLikeMessage(int $senderId, AppPostComment $comment): void
    {
        // 获取帖子封面
        $coverUrl = null;
        $post = AppPostBase::query()
            ->select(['post_id', 'cover'])
            ->where('post_id', $comment->post_id)
            ->first();

        if ($post && isset($post->cover['url'])) {
            $coverUrl = $post->cover['url'];
        }

        // 发送点赞消息
        MessageService::createLikeMessage(
            $senderId,
            $comment->member_id,
            $comment->comment_id,
            MessageType::TARGET_COMMENT,
            mb_substr($comment->content, 0, 50),
            $coverUrl
        );
    }
}
