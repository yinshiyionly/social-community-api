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
     * 获取帖子评论列表（游标分页）
     *
     * @param Request $request
     * @param int $postId 帖子ID
     */
    public function list(Request $request, int $postId)
    {
        $memberId = $this->getMemberId($request);
        $cursor = $request->input('cursor');
        $pageSize = $request->input('pageSize', 10);

        try {
            $result = $this->commentService->getCommentList($postId, $memberId, $cursor, $pageSize);

            // 设置点赞状态到 Resource
            PostCommentResource::setLikedCommentIds($result['likedCommentIds']);

            return AppApiResponse::cursorPaginate($result['paginator'], PostCommentResource::class);
        } catch (\Exception $e) {
            Log::error('获取评论列表失败', [
                'post_id' => $postId,
                'error' => $e->getMessage()
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 获取帖子评论列表（普通分页）
     *
     * @param Request $request
     * @param int $postId 帖子ID
     */
    public function listPaginate(Request $request, int $postId)
    {
        $memberId = $this->getMemberId($request);
        $page = (int) $request->input('page', 1);
        $pageSize = (int) $request->input('pageSize', 10);

        try {
            $result = $this->commentService->getCommentListPaginate($postId, $memberId, $page, $pageSize);

            // 设置点赞状态到 Resource
            PostCommentResource::setLikedCommentIds($result['likedCommentIds']);

            return AppApiResponse::normalPaginate($result['paginator'], PostCommentResource::class);
        } catch (\Exception $e) {
            Log::error('获取评论列表失败', [
                'post_id' => $postId,
                'error' => $e->getMessage()
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 获取评论的回复列表（游标分页）
     *
     * @param Request $request
     * @param int $commentId 评论ID
     */
    public function replies(Request $request, int $commentId)
    {
        $memberId = $this->getMemberId($request);
        $cursor = $request->input('cursor');
        $pageSize = $request->input('pageSize', 10);

        try {
            $result = $this->commentService->getReplyList($commentId, $memberId, $cursor, $pageSize);

            // 设置点赞状态到 Resource
            PostCommentReplyResource::setLikedCommentIds($result['likedCommentIds']);

            return AppApiResponse::cursorPaginate($result['paginator'], PostCommentReplyResource::class);
        } catch (\Exception $e) {
            Log::error('获取回复列表失败', [
                'comment_id' => $commentId,
                'error' => $e->getMessage()
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 获取评论的回复列表（普通分页）
     *
     * @param Request $request
     * @param int $commentId 评论ID
     */
    public function repliesPaginate(Request $request, int $commentId)
    {
        $memberId = $this->getMemberId($request);
        $page = (int) $request->input('page', 1);
        $pageSize = (int) $request->input('pageSize', 10);

        try {
            $result = $this->commentService->getReplyListPaginate($commentId, $memberId, $page, $pageSize);

            // 设置点赞状态到 Resource
            PostCommentReplyResource::setLikedCommentIds($result['likedCommentIds']);

            return AppApiResponse::normalPaginate($result['paginator'], PostCommentReplyResource::class);
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

        // 获取IP和地域信息
        $ipAddress = $this->getClientIp($request);
        $ipRegion = $this->getIpRegion($ipAddress);

        try {
            $result = $this->commentService->createComment(
                $memberId,
                $postId,
                $content,
                $parentId,
                $replyToMemberId,
                $ipAddress,
                $ipRegion
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

    /**
     * 点赞评论
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function like(Request $request)
    {
        $memberId = $this->getMemberId($request);
        $commentId = $request->input('id', 0);

        if (empty($commentId)) {
            return AppApiResponse::error('评论ID不能为空');
        }

        try {
            $result = $this->commentService->likeComment($memberId, $commentId);

            if (!$result['success']) {
                if ($result['message'] === 'comment_not_found') {
                    return AppApiResponse::dataNotFound('评论不存在');
                }
                if ($result['message'] === 'already_liked') {
                    return AppApiResponse::error('已点赞');
                }
                return AppApiResponse::error('点赞失败');
            }

            return AppApiResponse::success([
                'data' => [
                    'likeCount' => $result['likeCount']
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('点赞评论失败', [
                'member_id' => $memberId,
                'comment_id' => $commentId,
                'error' => $e->getMessage()
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 取消点赞评论
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function unlike(Request $request)
    {
        $memberId = $this->getMemberId($request);
        $commentId = $request->input('id', 0);

        if (empty($commentId)) {
            return AppApiResponse::error('评论ID不能为空');
        }

        try {
            $result = $this->commentService->unlikeComment($memberId, $commentId);

            if (!$result['success']) {
                if ($result['message'] === 'comment_not_found') {
                    return AppApiResponse::dataNotFound('评论不存在');
                }
                if ($result['message'] === 'not_liked') {
                    return AppApiResponse::error('未点赞');
                }
                return AppApiResponse::error('取消点赞失败');
            }

            return AppApiResponse::success([
                'data' => [
                    'likeCount' => $result['likeCount']
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('取消点赞评论失败', [
                'member_id' => $memberId,
                'comment_id' => $commentId,
                'error' => $e->getMessage()
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 获取客户端真实IP
     *
     * @param Request $request
     * @return string
     */
    protected function getClientIp(Request $request): string
    {
        $headers = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
        ];

        foreach ($headers as $header) {
            if ($request->server($header)) {
                $ips = explode(',', $request->server($header));
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return $request->ip() ?: '';
    }

    /**
     * 根据IP获取归属地
     *
     * @param string $ip
     * @return string
     */
    protected function getIpRegion(string $ip): string
    {
        if (empty($ip) || $ip === '127.0.0.1' || $ip === '::1') {
            return '';
        }

        // 检查是否是私有IP
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return '';
        }

        // TODO: 集成第三方IP地理位置服务（如 ip2region、GeoIP2 等）
        // 这里返回空字符串，实际项目中需要接入IP解析服务
        return '';
    }
}
