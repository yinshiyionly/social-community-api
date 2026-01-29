<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Resources\App\AppApiResponse;
use App\Http\Resources\App\Member\MemberFansListResource;
use App\Http\Resources\App\Member\MemberFollowingListResource;
use App\Http\Resources\App\MemberCollectionListResource;
use App\Http\Resources\App\MemberPostListResource;
use App\Http\Resources\App\MemberProfileResource;
use App\Services\App\MemberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MemberController extends Controller
{
    /**
     * @var MemberService
     */
    protected $memberService;

    public function __construct(MemberService $memberService)
    {
        $this->memberService = $memberService;
    }

    /**
     * 获取当前登录会员ID（可选鉴权）
     *
     * @param Request $request
     * @return int|null
     */
    protected function getMemberId(Request $request)
    {
        return $request->attributes->get('member_id');
    }

    /**
     * 获取用户主页详情
     *
     * @param Request $request
     * @param int $id 目标用户ID
     * @return JsonResponse
     */
    public function profile(Request $request, int $id): JsonResponse
    {
        $currentMemberId = $this->getMemberId($request);

        try {
            // 获取用户信息
            $member = $this->memberService->getMemberProfile($id);

            if (!$member) {
                return AppApiResponse::dataNotFound('用户不存在');
            }

            // 获取帖子总数
            $postCount = $this->memberService->getMemberPostCount($id);

            // 检查是否已关注（仅登录用户且非本人）
            $isFollowed = false;
            if ($currentMemberId && $currentMemberId !== $id) {
                $isFollowed = $this->memberService->isFollowing($currentMemberId, $id);
            }

            return AppApiResponse::resource(
                $member,
                MemberProfileResource::class,
                'success',
                ['postCount' => $postCount, 'isFollowed' => $isFollowed]
            );
        } catch (\Exception $e) {
            Log::error('获取用户主页失败', [
                'target_member_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 获取用户帖子列表
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function posts(Request $request): JsonResponse
    {
        $page = (int)$request->input('page', 1);
        $pageSize = (int)$request->input('pageSize', 10);
        $id = (int)$request->input('member_id', 0);
        if (empty($id)) {
            $id = $request->attributes->get('member_id');
        }

        try {
            // 检查用户是否存在
            $member = $this->memberService->getMemberProfile($id);

            if (!$member) {
                return AppApiResponse::dataNotFound('用户不存在');
            }

            // 获取帖子列表
            $posts = $this->memberService->getMemberPosts($id, $page, $pageSize);

            return AppApiResponse::normalPaginate($posts, MemberPostListResource::class);
        } catch (\Exception $e) {
            Log::error('获取用户帖子列表失败', [
                'target_member_id' => $id,
                'page' => $page,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 获取个人收藏帖子列表
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function collections(Request $request): JsonResponse
    {
        $memberId = $this->getMemberId($request);
        $page = (int)$request->input('page', 1);
        $pageSize = (int)$request->input('pageSize', 10);

        try {
            $collections = $this->memberService->getMemberCollections($memberId, $page, $pageSize);

            // 过滤掉已删除的帖子
            $items = collect($collections->items())
                ->map(function ($item) {
                    return (new MemberCollectionListResource($item))->resolve();
                })
                ->filter()
                ->values()
                ->toArray();

            return response()->json([
                'code' => 200,
                'msg' => 'success',
                'total' => $collections->total(),
                'rows' => $items,
            ]);
        } catch (\Exception $e) {
            Log::error('获取收藏帖子列表失败', [
                'member_id' => $memberId,
                'page' => $page,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 获取个人粉丝列表
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function fans(Request $request): JsonResponse
    {
        $memberId = $this->getMemberId($request);
        $page = (int)$request->input('page', 1);
        $pageSize = (int)$request->input('pageSize', 10);

        try {
            $fans = $this->memberService->getMemberFans($memberId, $page, $pageSize);

            // 批量查询当前用户对粉丝的关注状态
            $fanMemberIds = collect($fans->items())
                ->pluck('member_id')
                ->filter()
                ->toArray();
            $followedIds = $this->memberService->getFollowedMemberIds($memberId, $fanMemberIds);

            // 将关注状态附加到每个粉丝记录
            foreach ($fans->items() as $fan) {
                $fan->is_followed = in_array($fan->member_id, $followedIds);
            }

            return AppApiResponse::normalPaginate($fans, MemberFansListResource::class);
        } catch (\Exception $e) {
            Log::error('获取粉丝列表失败', [
                'member_id' => $memberId,
                'page' => $page,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 获取个人关注列表
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function followings(Request $request): JsonResponse
    {
        $memberId = $this->getMemberId($request);
        $page = (int)$request->input('page', 1);
        $pageSize = (int)$request->input('pageSize', 10);

        try {
            $followings = $this->memberService->getMemberFollowings($memberId, $page, $pageSize);

            return AppApiResponse::normalPaginate($followings, MemberFollowingListResource::class);
        } catch (\Exception $e) {
            Log::error('获取关注列表失败', [
                'member_id' => $memberId,
                'page' => $page,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 修改用户头像
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateAvatar(Request $request): JsonResponse
    {
        $memberId = $this->getMemberId($request);
        $avatar = $request->input('avatar');

        if (empty($avatar)) {
            return AppApiResponse::error('头像不能为空');
        }

        try {
            $this->memberService->updateAvatar($memberId, $avatar);

            return AppApiResponse::success();
        } catch (\Exception $e) {
            Log::error('修改头像失败', [
                'member_id' => $memberId,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 修改用户昵称
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateNickname(Request $request): JsonResponse
    {
        $memberId = $this->getMemberId($request);
        $nickname = $request->input('nickname');

        if (empty($nickname)) {
            return AppApiResponse::error('昵称不能为空');
        }

        if (mb_strlen($nickname) > 20) {
            return AppApiResponse::error('昵称不能超过20个字符');
        }

        try {
            $this->memberService->updateNickname($memberId, $nickname);

            return AppApiResponse::success();
        } catch (\Exception $e) {
            Log::error('修改昵称失败', [
                'member_id' => $memberId,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }
}
