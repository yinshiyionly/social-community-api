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
use App\Services\App\FollowService;
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

    public function __construct(FollowService $followService)
    {
        $this->followService = $followService;
    }

    /**
     * 获取当前登录会员ID
     *
     * @param Request $request
     * @return int
     */
    protected function getMemberId(Request $request): int
    {
        return (int) $request->attributes->get('member_id');
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
     * @return JsonResponse
     */
    public function recommend(): JsonResponse
    {
        try {
            $recommendMembers = $this->followService->getRecommendMembers();

            return AppApiResponse::collection($recommendMembers, RecommendMemberResource::class);
        } catch (\Exception $e) {
            Log::error('获取推荐用户失败', [
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 关注用户
     *
     * @param FollowRequest $request
     * @param int $id 被关注用户ID
     * @return JsonResponse
     */
    public function follow(FollowRequest $request, int $id): JsonResponse
    {
        $memberId = $this->getMemberId($request);
        $source = $request->getSource();

        try {
            $result = $this->followService->followMember($memberId, $id, $source);

            if (!$result['success']) {
                if ($result['message'] === 'self_follow') {
                    return AppApiResponse::error('操作失败');
                }
                if ($result['message'] === 'not_found') {
                    return AppApiResponse::dataNotFound('用户不存在');
                }
                return AppApiResponse::error('操作失败');
            }

            return AppApiResponse::success([
                'data' => ['isFollowing' => $result['is_following']]
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
     * @param int $id 被关注用户ID
     * @return JsonResponse
     */
    public function unfollow(Request $request, int $id): JsonResponse
    {
        $memberId = $this->getMemberId($request);

        try {
            $result = $this->followService->unfollowMember($memberId, $id);

            return AppApiResponse::success([
                'data' => ['isFollowing' => $result['is_following']]
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
            $posts = $this->followService->getFollowingPosts($memberId, $page, $pageSize);

            return AppApiResponse::paginate($posts, FollowPostListResource::class);
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
}
