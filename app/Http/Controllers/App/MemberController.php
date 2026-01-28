<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Resources\App\AppApiResponse;
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
     * @param int $id 目标用户ID
     * @return JsonResponse
     */
    public function posts(Request $request, int $id): JsonResponse
    {
        $page = (int)$request->input('page', 1);
        $pageSize = (int)$request->input('pageSize', 10);

        try {
            // 检查用户是否存在
            $member = $this->memberService->getMemberProfile($id);

            if (!$member) {
                return AppApiResponse::dataNotFound('用户不存在');
            }

            // 获取帖子列表
            $posts = $this->memberService->getMemberPosts($id, $page, $pageSize);

            return AppApiResponse::paginate($posts, MemberPostListResource::class);
        } catch (\Exception $e) {
            Log::error('获取用户帖子列表失败', [
                'target_member_id' => $id,
                'page' => $page,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }
}
