<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\App\PostListRequest;
use App\Http\Resources\App\AppApiResponse;
use App\Http\Resources\App\PostListResource;
use App\Http\Resources\App\PostResource;
use App\Services\App\PostService;
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

            // 如果用户已登录，获取收藏状态并注入到 Resource
            if ($memberId) {
                $postIds = $posts->pluck('post_id')->toArray();
                $collectedPostIds = $this->postService->getCollectedPostIds($memberId, $postIds);
                PostListResource::setCollectedPostIds($collectedPostIds);
            } else {
                PostListResource::setCollectedPostIds([]);
            }

            return AppApiResponse::cursorPaginate($posts, PostListResource::class);
        } catch (\Exception $e) {
            Log::error('获取帖子列表失败', [
                'cursor' => $cursor,
                'pageSize' => $pageSize,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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

            // 检查收藏状态
            $isCollected = false;
            if ($memberId) {
                $isCollected = $this->postService->isPostCollected($memberId, $id);
            }

            return AppApiResponse::resource(
                $post,
                PostResource::class,
                'success',
                ['is_collected' => $isCollected]
            );
        } catch (\Exception $e) {
            Log::error('获取帖子详情失败', [
                'post_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 收藏帖子
     *
     * @param Request $request
     * @param int $id 帖子ID
     */
    public function collect(Request $request, int $id)
    {
        $memberId = $this->getMemberId($request);

        try {
            $result = $this->postService->collectPost($memberId, $id);

            if (!$result['success'] && $result['message'] === 'not_found') {
                return AppApiResponse::dataNotFound('内容不存在');
            }

            return AppApiResponse::success([
                'isCollected' => $result['is_collected'],
            ]);
        } catch (\Exception $e) {
            Log::error('收藏帖子失败', [
                'member_id' => $memberId,
                'post_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 取消收藏帖子
     *
     * @param Request $request
     * @param int $id 帖子ID
     */
    public function uncollect(Request $request, int $id)
    {
        $memberId = $this->getMemberId($request);

        try {
            $result = $this->postService->uncollectPost($memberId, $id);

            if (!$result['success'] && $result['message'] === 'not_found') {
                return AppApiResponse::dataNotFound('内容不存在');
            }

            return AppApiResponse::success([
                'isCollected' => $result['is_collected'],
            ]);
        } catch (\Exception $e) {
            Log::error('取消收藏帖子失败', [
                'member_id' => $memberId,
                'post_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return AppApiResponse::serverError();
        }
    }
}
