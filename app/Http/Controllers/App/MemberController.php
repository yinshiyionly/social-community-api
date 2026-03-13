<?php

namespace App\Http\Controllers\App;

use App\Constant\AppResponseCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\Member\AccountCancelRequest;
use App\Http\Requests\App\Member\MemberUpdateRequest;
use App\Http\Resources\App\AppApiResponse;
use App\Http\Resources\App\Member\MemberCollectionListResource;
use App\Http\Resources\App\Member\MemberFansListResource;
use App\Http\Resources\App\Member\MemberFollowingListResource;
use App\Http\Resources\App\Member\MemberInfoResource;
use App\Http\Resources\App\MemberPostListResource;
use App\Http\Resources\App\MemberProfileResource;
use App\Services\App\MemberService;
use App\Services\App\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * App 端会员中心控制器。
 *
 * 职责：
 * 1. 提供会员主页、个人资料、关注关系等查询接口；
 * 2. 提供头像、昵称、资料更新及账号注销等写操作入口；
 * 3. 统一捕获异常并输出 AppApiResponse，避免暴露内部异常细节。
 */
class MemberController extends Controller
{
    /**
     * @var MemberService
     */
    protected $memberService;

    /**
     * @var PostService
     */
    protected $postService;

    public function __construct(MemberService $memberService, PostService $postService)
    {
        $this->memberService = $memberService;
        $this->postService = $postService;
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
     * @return JsonResponse
     */
    public function profile(Request $request): JsonResponse
    {
        $currentMemberId = $this->getMemberId($request);

        try {
            $id = $request->get('memberId', 0);
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
     * 规则：
     * 1. memberId 为空时默认查询当前登录用户发布内容；
     * 2. isLiked/isFavorited 固定按“当前登录用户”视角计算；
     * 3. isOwned 按“当前登录用户 member_id 是否等于帖子作者 member_id”计算；
     * 4. 通过批量查询注入 Resource，避免列表序列化阶段产生 N+1。
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function posts(Request $request): JsonResponse
    {
        $currentMemberId = (int)$this->getMemberId($request);
        $page = (int)$request->input('page', 1);
        $pageSize = (int)$request->input('pageSize', 10);
        $id = (int)$request->get('memberId', 0);
        if (empty($id)) {
            $id = $currentMemberId;
        }

        try {
            // 检查用户是否存在
            $member = $this->memberService->getMemberProfile($id);

            if (!$member) {
                return AppApiResponse::dataNotFound('用户不存在');
            }

            // 获取帖子列表
            $posts = $this->memberService->getMemberPosts($id, $page, $pageSize);
            $postIds = collect($posts->items())
                ->pluck('post_id')
                ->filter()
                ->values()
                ->toArray();

            if ($currentMemberId > 0) {
                $likedPostIds = $this->postService->getLikedPostIds($currentMemberId, $postIds);
                $favoritedPostIds = $this->postService->getCollectedPostIds($currentMemberId, $postIds);
                MemberPostListResource::setInteractionData($likedPostIds, $favoritedPostIds);
            } else {
                // 未登录态兜底为 false，保证响应字段稳定。
                MemberPostListResource::setInteractionData([], []);
            }
            MemberPostListResource::setCurrentMemberId($currentMemberId);

            return AppApiResponse::memberPaginate($posts, MemberPostListResource::class);
        } catch (\Exception $e) {
            Log::error('获取用户帖子列表失败', [
                'current_member_id' => $currentMemberId,
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
     * 规则：
     * 1. memberId 为空时默认查询当前登录用户收藏；
     * 2. isLiked/isFavorited 固定按“当前登录用户”视角计算；
     * 3. isOwned 按“当前登录用户 member_id 是否等于帖子作者 member_id”计算；
     * 4. 收藏关联帖子不存在时由 Resource 返回 null，保持历史兼容行为。
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function collections(Request $request): JsonResponse
    {
        $currentMemberId = (int)$this->getMemberId($request);
        $page = (int)$request->input('page', 1);
        $pageSize = (int)$request->input('pageSize', 10);
        $targetMemberId = (int)$request->get('memberId', 0);
        if ($targetMemberId <= 0) {
            $targetMemberId = $currentMemberId;
        }

        try {
            $collections = $this->memberService->getMemberCollections($targetMemberId, $page, $pageSize);
            $postIds = collect($collections->items())
                ->pluck('post.post_id')
                ->filter()
                ->values()
                ->toArray();

            if ($currentMemberId > 0) {
                $likedPostIds = $this->postService->getLikedPostIds($currentMemberId, $postIds);
                $favoritedPostIds = $this->postService->getCollectedPostIds($currentMemberId, $postIds);
                MemberCollectionListResource::setInteractionData($likedPostIds, $favoritedPostIds);
            } else {
                // 未登录态兜底为 false，保证响应字段稳定。
                MemberCollectionListResource::setInteractionData([], []);
            }
            MemberCollectionListResource::setCurrentMemberId($currentMemberId);

            return AppApiResponse::memberPaginate($collections, MemberCollectionListResource::class);
        } catch (\Exception $e) {
            Log::error('获取收藏帖子列表失败', [
                'current_member_id' => $currentMemberId,
                'target_member_id' => $targetMemberId,
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

    /**
     * 获取当前登录用户个人信息
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function info(Request $request): JsonResponse
    {
        $memberId = $this->getMemberId($request);

        try {
            $member = $this->memberService->getMemberInfo($memberId);

            if (!$member) {
                return AppApiResponse::dataNotFound('用户不存在');
            }

            return AppApiResponse::resource($member, MemberInfoResource::class);
        } catch (\Exception $e) {
            Log::error('获取个人信息失败', [
                'member_id' => $memberId,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 更新当前登录用户个人信息
     *
     * @param MemberUpdateRequest $request
     * @return JsonResponse
     */
    public function update(MemberUpdateRequest $request): JsonResponse
    {
        $memberId = $this->getMemberId($request);
        $data = $request->validated();

        try {
            $this->memberService->updateMemberInfo($memberId, $data);

            return AppApiResponse::success();
        } catch (\Exception $e) {
            Log::error('更新个人信息失败', [
                'member_id' => $memberId,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 注销当前登录账号。
     *
     * 接口用途：
     * - App 端用户主动注销账号，成功后前端清理本地登录态。
     *
     * 关键输入：
     * - confirmText 可选，传入时必须为“注销账号”；
     * - reason 可选，仅用于日志审计，不落业务库字段。
     *
     * 关键输出：
     * - 成功返回 AppApiResponse::success，msg 为“账号已注销”。
     *
     * 失败分支：
     * - 参数错误返回 400；
     * - 官方账号不允许注销返回 403；
     * - 用户不存在返回 404；
     * - 其他异常统一返回 500，避免泄露内部细节。
     *
     * @param AccountCancelRequest $request
     * @return JsonResponse
     */
    public function cancel(AccountCancelRequest $request): JsonResponse
    {
        $memberId = (int)$this->getMemberId($request);
        $reason = trim((string)$request->input('reason', ''));

        try {
            $result = $this->memberService->cancelAccount(
                $memberId,
                $reason,
                $request->ip(),
                (string)$request->userAgent()
            );

            if ($result['success']) {
                return AppApiResponse::success([], '账号已注销');
            }

            $code = (int)($result['code'] ?? AppResponseCode::SERVER_ERROR);
            $message = (string)($result['message'] ?? '注销失败，请稍后重试');

            if ($code === AppResponseCode::DATA_NOT_FOUND) {
                return AppApiResponse::dataNotFound($message);
            }

            if ($code === AppResponseCode::FORBIDDEN) {
                return AppApiResponse::forbidden($message);
            }

            if ($code === AppResponseCode::INVALID_PARAMS) {
                return AppApiResponse::error($message, AppResponseCode::INVALID_PARAMS);
            }

            return AppApiResponse::serverError($message);
        } catch (\Exception $e) {
            Log::error('注销账号失败', [
                'member_id' => $memberId,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError('注销失败，请稍后重试');
        }
    }
}
