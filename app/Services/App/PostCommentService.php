<?php

namespace App\Services\App;

use App\Constant\MessageType;
use App\Models\App\AppPostBase;
use App\Models\App\AppPostComment;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Facades\DB;

class PostCommentService
{
    /**
     * 获取帖子评论列表（游标分页）
     *
     * @param int $postId 帖子ID
     * @param string|null $cursor 游标
     * @param int $pageSize 每页数量
     * @return CursorPaginator
     */
    public function getCommentList(int $postId, ?string $cursor = null, int $pageSize = 10): CursorPaginator
    {
        return AppPostComment::query()
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
    }

    /**
     * 获取评论的回复列表（游标分页）
     *
     * @param int $commentId 父评论ID
     * @param string|null $cursor 游标
     * @param int $pageSize 每页数量
     * @return CursorPaginator
     */
    public function getReplyList(int $commentId, ?string $cursor = null, int $pageSize = 10): CursorPaginator
    {
        return AppPostComment::query()
            ->with(['member', 'replyToMember'])
            ->byParent($commentId)
            ->normal()
            ->orderBy('created_at')
            ->cursorPaginate($pageSize, ['*'], 'cursor', $cursor);
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
        int $memberId,
        int $postId,
        string $content,
        int $parentId = 0,
        int $replyToMemberId = 0
    ): array {
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
            $post->incrementCommentCount();

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
                $post->decrementCommentCount();
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
}
