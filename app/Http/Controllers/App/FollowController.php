<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\App\FollowListRequest;
use App\Http\Requests\App\FollowPostListRequest;
use App\Http\Requests\App\FollowRequest;
use App\Http\Resources\App\AppApiResponse;
use App\Http\Resources\App\FollowMemberListResource;
use App\Http\Resources\App\FollowPostListResource;
use App\Http\Resources\App\RecommendMemberResource;
use App\Http\Resources\App\RecommendPostListResource;
use App\Services\App\FollowService;
use App\Services\App\RecommendPostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * 关注模块控制器
 */
class FollowController extends Controller
{
    /**
     * @var FollowService
     */
    protected $followService;

    /**
     * @var RecommendPostService
     */
    protected $recommendPostService;

    public function __construct(FollowService $followService, RecommendPostService $recommendPostService)
    {
        $this->followService = $followService;
        $this->recommendPostService = $recommendPostService;
    }

    /**
     * 获取当前登录会员ID
     *
     * @param Request $request
     * @return int
     */
    protected function getMemberId(Request $request): int
    {
        return (int)$request->attributes->get('member_id');
    }

    /**
     * 我关注的人列表
     *
     * @param FollowListRequest $request
     * @return JsonResponse
     */
    public function list(FollowListRequest $request): JsonResponse
    {
        $memberId = $this->getMemberId($request);
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 10);

        try {
            $followList = $this->followService->getFollowingList($memberId, $page, $pageSize);

            return AppApiResponse::normalPaginate($followList, FollowMemberListResource::class);
        } catch (\Exception $e) {
            Log::error('获取关注列表失败', [
                'member_id' => $memberId,
                'page' => $page,
                'pageSize' => $pageSize,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }


    /**
     * 可能感兴趣的人（推荐用户）
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function recommend(Request $request): JsonResponse
    {
        $memberId = $this->getMemberId($request);

        try {
            $recommendMembers = $this->followService->getRecommendMembers($memberId);

            return AppApiResponse::collection($recommendMembers, RecommendMemberResource::class);
        } catch (\Exception $e) {
            Log::error('获取推荐用户失败', [
                'member_id' => $memberId,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 关注用户
     *
     * @param FollowRequest $request
     * @return JsonResponse
     */
    public function follow(FollowRequest $request): JsonResponse
    {
        $memberId = $this->getMemberId($request);
        $source = $request->getSource();
        $id = $request->get('userId', 0);

        if (empty($id)) {
            return AppApiResponse::error('用户ID不能为空');
        }

        try {
            $result = $this->followService->followMember($memberId, $id, $source);

            if (!$result['success']) {
                if ($result['message'] === 'self_follow') {
                    return AppApiResponse::error('不能关注自己');
                }
                if ($result['message'] === 'not_found') {
                    return AppApiResponse::dataNotFound('用户不存在');
                }
                return AppApiResponse::error('操作失败');
            }

            return AppApiResponse::success([
                'data' => [
                    'isFollowing' => $result['is_following']
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('关注用户失败', [
                'member_id' => $memberId,
                'follow_member_id' => $id,
                'source' => $source,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 取消关注
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function unfollow(Request $request): JsonResponse
    {
        $memberId = $this->getMemberId($request);
        $id = $request->get('userId', 0);

        if (empty($id)) {
            return AppApiResponse::error('用户ID不能为空');
        }

        try {
            $result = $this->followService->unfollowMember($memberId, $id);

            return AppApiResponse::success([
                'data' => [
                    'isFollowing' => $result['is_following']
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('取消关注失败', [
                'member_id' => $memberId,
                'follow_member_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 关注的人的帖子列表
     *
     * @param FollowPostListRequest $request
     * @return JsonResponse
     */
    public function posts(FollowPostListRequest $request): JsonResponse
    {
        $memberId = $this->getMemberId($request);
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 10);

        try {
            $result = $this->followService->getFollowingPosts($memberId, $page, $pageSize);

            // 设置交互状态数据到 Resource
            FollowPostListResource::setInteractionData(
                $result['likedIds'],
                $result['collectedIds'],
                $result['followedIds']
            );

            return AppApiResponse::normalPaginate($result['posts'], FollowPostListResource::class);
        } catch (\Exception $e) {
            Log::error('获取关注用户帖子失败', [
                'member_id' => $memberId,
                'page' => $page,
                'pageSize' => $pageSize,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 获取推荐帖子列表（猜你喜欢）
     *
     * @param FollowPostListRequest $request
     * @return JsonResponse
     */
    public function recommendPosts(FollowPostListRequest $request): JsonResponse
    {
        $memberId = $this->getMemberId($request);
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 10);

        try {
            $result = $this->recommendPostService->getRecommendPosts($memberId, $page, $pageSize);

            // 设置交互状态数据到 Resource
            RecommendPostListResource::setInteractionData(
                $result['likedIds'],
                $result['collectedIds'],
                $result['followedIds']
            );

            return AppApiResponse::normalPaginate($result['posts'], RecommendPostListResource::class);
        } catch (\Exception $e) {
            Log::error('获取推荐帖子失败', [
                'member_id' => $memberId,
                'page' => $page,
                'pageSize' => $pageSize,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }
}
