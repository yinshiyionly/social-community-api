<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\App\PostCommentRequest;
use App\Http\Resources\App\AppApiResponse;
use App\Http\Resources\App\PostCommentResource;
use App\Http\Resources\App\PostCommentReplyResource;
use App\Services\App\PostCommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PostCommentController extends Controller
{
    /**
     * @var PostCommentService
     */
    protected $commentService;

    public function __construct(PostCommentService $commentService)
    {
        $this->commentService = $commentService;
    }

    /**
     * 获取当前登录会员ID
     *
     * @param Request $request
     * @return int|null
     */
    protected function getMemberId(Request $request)
    {
        return $request->attributes->get('member_id');
    }

    /**
     * 获取帖子评论列表
     *
     * @param Request $request
     * @param int $postId 帖子ID
     */
    public function list(Request $request, int $postId)
    {
        $cursor = $request->input('cursor');
        $pageSize = $request->input('pageSize', 10);

        try {
            $comments = $this->commentService->getCommentList($postId, $cursor, $pageSize);

            return AppApiResponse::cursorPaginate($comments, PostCommentResource::class);
        } catch (\Exception $e) {
            Log::error('获取评论列表失败', [
                'post_id' => $postId,
                'error' => $e->getMessage()
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 获取评论的回复列表
     *
     * @param Request $request
     * @param int $commentId 评论ID
     */
    public function replies(Request $request, int $commentId)
    {
        $cursor = $request->input('cursor');
        $pageSize = $request->input('pageSize', 10);

        try {
            $replies = $this->commentService->getReplyList($commentId, $cursor, $pageSize);

            return AppApiResponse::cursorPaginate($replies, PostCommentReplyResource::class);
        } catch (\Exception $e) {
            Log::error('获取回复列表失败', [
                'comment_id' => $commentId,
                'error' => $e->getMessage()
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 发表评论
     *
     * @param PostCommentRequest $request
     * @return JsonResponse
     */
    public function store(PostCommentRequest $request)
    {
        $memberId = $this->getMemberId($request);
        $postId = $request->input('id', 0);
        $content = $request->input('content');
        $parentId = $request->input('parent_id', 0);
        $replyToMemberId = $request->input('reply_to_member_id', 0);


        try {
            $result = $this->commentService->createComment(
                $memberId,
                $postId,
                $content,
                $parentId,
                $replyToMemberId
            );

            if (!$result['success']) {
                if ($result['message'] === 'post_not_found') {
                    return AppApiResponse::dataNotFound('内容不存在');
                }
                if ($result['message'] === 'parent_not_found') {
                    return AppApiResponse::dataNotFound('评论不存在');
                }
                return AppApiResponse::error('评论失败');
            }

            $comment = $result['comment'];
            $resourceClass = $comment->isTopLevel()
                ? PostCommentResource::class
                : PostCommentReplyResource::class;

            return AppApiResponse::resource($comment, $resourceClass);
        } catch (\Exception $e) {
            Log::error('发表评论失败', [
                'member_id' => $memberId,
                'post_id' => $postId,
                'error' => $e->getMessage()
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 删除评论
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(Request $request)
    {
        $memberId = $this->getMemberId($request);

        $commentId = $request->input('id', 0);
        if (empty($commentId)) {
            return AppApiResponse::error('评论ID不存在或错误');
        }

        try {

            $result = $this->commentService->deleteComment($memberId, $commentId);

            if (!$result['success']) {
                if ($result['message'] === 'not_found') {
                    return AppApiResponse::dataNotFound('评论不存在');
                }
                if ($result['message'] === 'forbidden') {
                    return AppApiResponse::forbidden('无权删除该评论');
                }
                return AppApiResponse::error('删除失败');
            }

            return AppApiResponse::success();
        } catch (\Exception $e) {
            Log::error('删除评论失败', [
                'member_id' => $memberId,
                'comment_id' => $commentId,
                'error' => $e->getMessage()
            ]);

            return AppApiResponse::serverError();
        }
    }
}
