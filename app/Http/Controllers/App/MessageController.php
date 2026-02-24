<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\App\MessageMarkReadRequest;
use App\Http\Resources\App\AppApiResponse;
use App\Http\Resources\App\MessageCommentResource;
use App\Http\Resources\App\MessageFollowResource;
use App\Http\Resources\App\MessageLikeCollectResource;
use App\Http\Resources\App\MessageSystemDetailResource;
use App\Http\Resources\App\MessageSystemResource;
use App\Services\App\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * 消息控制器
 */
class MessageController extends Controller
{
    /**
     * @var MessageService
     */
    protected $messageService;

    public function __construct(MessageService $messageService)
    {
        $this->messageService = $messageService;
    }

    /**
     * 获取当前登录会员ID
     *
     * @param Request $request
     * @return int
     */
    protected function getMemberId(Request $request): int
    {
        return $request->attributes->get('member_id');
    }

    /**
     * 获取消息未读数统计
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $memberId = $this->getMemberId($request);

        try {
            $data = $this->messageService->getUnreadCount($memberId);

            return AppApiResponse::success(['data' => $data]);
        } catch (\Exception $e) {
            Log::error('获取消息未读数失败', [
                'member_id' => $memberId,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 获取消息总列表（各分类概览）
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function overview(Request $request): JsonResponse
    {
        $memberId = $this->getMemberId($request);

        try {
            $data = $this->messageService->getMessageOverview($memberId);

            return AppApiResponse::success(['data' => $data]);
        } catch (\Exception $e) {
            Log::error('获取消息总列表失败', [
                'member_id' => $memberId,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }


    /**
     * 获取赞和收藏消息列表
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function likeAndCollect(Request $request): JsonResponse
    {
        $memberId = $this->getMemberId($request);
        $cursor = $request->input('cursor');
        $pageSize = $request->input('pageSize', 20);

        try {
            $messages = $this->messageService->getLikeAndCollectMessages($memberId, $cursor, $pageSize);

            return AppApiResponse::cursorPaginate($messages, MessageLikeCollectResource::class);
        } catch (\Exception $e) {
            Log::error('获取赞和收藏消息列表失败', [
                'member_id' => $memberId,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 获取评论消息列表
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function comment(Request $request): JsonResponse
    {
        $memberId = $this->getMemberId($request);
        $cursor = $request->input('cursor');
        $pageSize = $request->input('pageSize', 20);

        try {
            $messages = $this->messageService->getCommentMessages($memberId, $cursor, $pageSize);

            return AppApiResponse::cursorPaginate($messages, MessageCommentResource::class);
        } catch (\Exception $e) {
            Log::error('获取评论消息列表失败', [
                'member_id' => $memberId,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 获取关注消息列表
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function follow(Request $request): JsonResponse
    {
        $memberId = $this->getMemberId($request);
        $cursor = $request->input('cursor');
        $pageSize = $request->input('pageSize', 20);

        try {
            $messages = $this->messageService->getFollowMessages($memberId, $cursor, $pageSize);

            // 获取发送者ID列表，检查当前用户是否已关注这些用户
            $senderIds = $messages->pluck('sender_id')->unique()->toArray();
            $followedIds = $this->messageService->getFollowedMemberIds($memberId, $senderIds);
            MessageFollowResource::setFollowedMemberIds($followedIds);

            return AppApiResponse::cursorPaginate($messages, MessageFollowResource::class);
        } catch (\Exception $e) {
            Log::error('获取关注消息列表失败', [
                'member_id' => $memberId,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 获取系统消息列表
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function system(Request $request): JsonResponse
    {
        $memberId = $this->getMemberId($request);
        $cursor = $request->input('cursor');
        $pageSize = $request->input('pageSize', 20);

        try {
            $messages = $this->messageService->getSystemMessages($memberId, $cursor, $pageSize);

            return AppApiResponse::cursorPaginate($messages, MessageSystemResource::class);
        } catch (\Exception $e) {
            Log::error('获取系统消息列表失败', [
                'member_id' => $memberId,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 获取指定官方账号的消息列表（会话详情）
     *
     * @param Request $request
     * @param int $senderId 官方账号的会员ID
     * @return JsonResponse
     */
    public function systemBySender(Request $request, int $senderId): JsonResponse
    {
        $memberId = $this->getMemberId($request);
        $cursor = $request->input('cursor');
        $pageSize = $request->input('pageSize', 20);

        try {
            // 进入会话时标记该发送者的消息为已读
            $this->messageService->markSystemReadBySender($memberId, $senderId);

            $messages = $this->messageService->getSystemMessagesBySender($memberId, $senderId, $cursor, $pageSize);

            return AppApiResponse::cursorPaginate($messages, MessageSystemResource::class);
        } catch (\Exception $e) {
            Log::error('获取官方账号消息列表失败', [
                'member_id' => $memberId,
                'sender_id' => $senderId,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }


    /**
     * 获取系统消息详情
     *
     * @param Request $request
     * @param int $id 消息ID
     * @return JsonResponse
     */
    public function systemDetail(Request $request, int $id): JsonResponse
    {
        $memberId = $this->getMemberId($request);

        try {
            $message = $this->messageService->getSystemDetail($memberId, $id);

            if (!$message) {
                return AppApiResponse::dataNotFound('消息不存在');
            }

            return AppApiResponse::resource($message, MessageSystemDetailResource::class);
        } catch (\Exception $e) {
            Log::error('获取系统消息详情失败', [
                'member_id' => $memberId,
                'message_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 标记消息为已读
     *
     * @param MessageMarkReadRequest $request
     * @return JsonResponse
     */
    public function markRead(MessageMarkReadRequest $request): JsonResponse
    {
        $memberId = $this->getMemberId($request);
        $type = $request->input('type');

        try {
            $this->messageService->markAsRead($memberId, $type);

            return AppApiResponse::success();
        } catch (\Exception $e) {
            Log::error('标记消息已读失败', [
                'member_id' => $memberId,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }
}
