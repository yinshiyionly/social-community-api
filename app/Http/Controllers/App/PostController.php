<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\App\PostListRequest;
use App\Http\Requests\App\PostPageRequest;
use App\Http\Resources\App\AppApiResponse;
use App\Http\Resources\App\PostListResource;
use App\Http\Resources\App\PostResource;
use App\Services\App\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PostController extends Controller
{
    /**
     * @var PostService
     */
    protected $postService;

    public function __construct(PostService $postService)
    {
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
     * 获取帖子列表（游标分页）
     */
    public function list(PostListRequest $request)
    {
        $cursor = $request->input('cursor');
        $pageSize = $request->input('pageSize', 10);
        $memberId = $this->getMemberId($request);

        try {
            $posts = $this->postService->getPostList($cursor, $pageSize);

            // 如果用户已登录，获取收藏和点赞状态并注入到 Resource
            if ($memberId) {
                $postIds = $posts->pluck('post_id')->toArray();
                $collectedPostIds = $this->postService->getCollectedPostIds($memberId, $postIds);
                $likedPostIds = $this->postService->getLikedPostIds($memberId, $postIds);
                PostListResource::setCollectedPostIds($collectedPostIds);
                PostListResource::setLikedPostIds($likedPostIds);
            } else {
                PostListResource::setCollectedPostIds([]);
                PostListResource::setLikedPostIds([]);
            }

            return AppApiResponse::cursorPaginate($posts, PostListResource::class);
        } catch (\Exception $e) {
            Log::error('获取帖子列表失败', [
                'cursor' => $cursor,
                'pageSize' => $pageSize,
                'error' => $e->getMessage()
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 获取帖子列表（普通分页）
     */
    public function page(PostPageRequest $request)
    {
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 10);
        $memberId = $this->getMemberId($request);

        try {
            $posts = $this->postService->getPostListPaginate($page, $pageSize);

            // 如果用户已登录，获取收藏和点赞状态并注入到 Resource
            if ($memberId) {
                $postIds = $posts->pluck('post_id')->toArray();
                $collectedPostIds = $this->postService->getCollectedPostIds($memberId, $postIds);
                $likedPostIds = $this->postService->getLikedPostIds($memberId, $postIds);
                PostListResource::setCollectedPostIds($collectedPostIds);
                PostListResource::setLikedPostIds($likedPostIds);
            } else {
                PostListResource::setCollectedPostIds([]);
                PostListResource::setLikedPostIds([]);
            }

            return AppApiResponse::paginate($posts, PostListResource::class);
        } catch (\Exception $e) {
            Log::error('获取帖子列表失败', [
                'page' => $page,
                'pageSize' => $pageSize,
                'error' => $e->getMessage()
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 获取帖子详情
     *
     * @param Request $request
     * @param int $id 帖子ID
     */
    public function detail(Request $request, int $id)
    {
        $memberId = $this->getMemberId($request);

        try {
            $post = $this->postService->getPostDetail($id);

            if (!$post) {
                return AppApiResponse::dataNotFound('内容不存在');
            }

            // 增加浏览量
            $this->postService->incrementViewCount($post);

            // 检查收藏和点赞状态
            $isCollected = false;
            $isLiked = false;
            if ($memberId) {
                $isCollected = $this->postService->isPostCollected($memberId, $id);
                $isLiked = $this->postService->isPostLiked($memberId, $id);
            }

            return AppApiResponse::resource(
                $post,
                PostResource::class,
                'success',
                ['isCollected' => $isCollected, 'isLiked' => $isLiked]
            );
        } catch (\Exception $e) {
            Log::error('获取帖子详情失败', [
                'post_id' => $id,
                'error' => $e->getMessage()
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 收藏帖子
     *
     * @param Request $request
     * @param int $id 帖子ID
     * @return JsonResponse
     */
    public function collect(Request $request, int $id)
    {
        $memberId = $this->getMemberId($request);

        try {
            $result = $this->postService->collectPost($memberId, $id);

            if (!$result['success'] && $result['message'] === 'not_found') {
                return AppApiResponse::dataNotFound('内容不存在');
            }

            return AppApiResponse::success(['data' => [
                'isCollected' => $result['is_collected']
            ]]);
        } catch (\Exception $e) {
            Log::error('收藏帖子失败', [
                'member_id' => $memberId,
                'post_id' => $id,
                'error' => $e->getMessage()
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 取消收藏帖子
     *
     * @param Request $request
     * @param int $id 帖子ID
     * @return JsonResponse
     */
    public function uncollect(Request $request, int $id)
    {
        $memberId = $this->getMemberId($request);

        try {
            $result = $this->postService->uncollectPost($memberId, $id);

            if (!$result['success'] && $result['message'] === 'not_found') {
                return AppApiResponse::dataNotFound('内容不存在');
            }

            return AppApiResponse::success(['data' => [
                'isCollected' => $result['is_collected']
            ]]);
        } catch (\Exception $e) {
            Log::error('取消收藏帖子失败', [
                'member_id' => $memberId,
                'post_id' => $id,
                'error' => $e->getMessage()
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 点赞帖子
     *
     * @param Request $request
     * @param int $id 帖子ID
     * @return JsonResponse
     */
    public function like(Request $request, int $id)
    {
        $memberId = $this->getMemberId($request);

        try {
            $result = $this->postService->likePost($memberId, $id);

            if (!$result['success'] && $result['message'] === 'not_found') {
                return AppApiResponse::dataNotFound('内容不存在');
            }

            return AppApiResponse::success(['data' => [
                'isLiked' => $result['is_liked']
            ]]);
        } catch (\Exception $e) {
            Log::error('点赞帖子失败', [
                'member_id' => $memberId,
                'post_id' => $id,
                'error' => $e->getMessage()
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 取消点赞帖子
     *
     * @param Request $request
     * @param int $id 帖子ID
     * @return JsonResponse
     */
    public function unlike(Request $request, int $id)
    {
        $memberId = $this->getMemberId($request);

        try {
            $result = $this->postService->unlikePost($memberId, $id);

            if (!$result['success'] && $result['message'] === 'not_found') {
                return AppApiResponse::dataNotFound('内容不存在');
            }

            return AppApiResponse::success(['data' => [
                'isLiked' => $result['is_liked']
            ]]);
        } catch (\Exception $e) {
            Log::error('取消点赞帖子失败', [
                'member_id' => $memberId,
                'post_id' => $id,
                'error' => $e->getMessage()
            ]);

            return AppApiResponse::serverError();
        }
    }
}
