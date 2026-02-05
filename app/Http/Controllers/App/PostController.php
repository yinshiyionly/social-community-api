<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\App\ArticlePostStoreRequest;
use App\Http\Requests\App\ImageTextPostStoreRequest;
use App\Http\Requests\App\PostListRequest;
use App\Http\Requests\App\PostPageRequest;
use App\Http\Requests\App\PostStoreRequest;
use App\Http\Requests\App\VideoPostStoreRequest;
use App\Http\Resources\App\AppApiResponse;
use App\Http\Resources\App\ArticlePostResource;
use App\Http\Resources\App\ImageTextPostResource;
use App\Http\Resources\App\PostListResource;
use App\Http\Resources\App\PostResource;
use App\Http\Resources\App\VideoFeedResource;
use App\Http\Resources\App\VideoPostResource;
use App\Models\App\AppPostBase;
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
     * 发表帖子
     *
     * @param PostStoreRequest $request
     * @return JsonResponse
     * @see storeVideo() 发表视频动态
     * @see storeArticle() 发表文章动态
     *
     * @deprecated 建议使用新接口：storeImageText()、storeVideo()、storeArticle()
     * @see storeImageText() 发表图文动态
     */
    public function store(PostStoreRequest $request)
    {
        $memberId = $this->getMemberId($request);
        $data = $request->validatedWithDefaults();

        try {
            $postId = $this->postService->createPost($memberId, $data);

            return AppApiResponse::success([
                'data' => [
                    'post_id' => $postId
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('发表帖子失败', [
                'member_id' => $memberId,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 发表图文动态
     *
     * @param ImageTextPostStoreRequest $request
     * @return JsonResponse
     */
    public function storeImageText(ImageTextPostStoreRequest $request): JsonResponse
    {
        $memberId = $this->getMemberId($request);
        $data = $request->validatedWithDefaults();

        try {
            $postId = $this->postService->createPost($memberId, $data);

            return AppApiResponse::success([
                'data' => ['post_id' => $postId]
            ]);
        } catch (\Exception $e) {
            Log::error('发表图文动态失败', [
                'member_id' => $memberId,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 发表视频动态
     *
     * @param VideoPostStoreRequest $request
     * @return JsonResponse
     */
    public function storeVideo(VideoPostStoreRequest $request): JsonResponse
    {
        $memberId = $this->getMemberId($request);
        $data = $request->validatedWithDefaults();

        try {
            $postId = $this->postService->createPost($memberId, $data);

            return AppApiResponse::success([
                'data' => ['post_id' => $postId]
            ]);
        } catch (\Exception $e) {
            Log::error('发表视频动态失败', [
                'member_id' => $memberId,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 发表文章动态
     *
     * @param ArticlePostStoreRequest $request
     * @return JsonResponse
     */
    public function storeArticle(ArticlePostStoreRequest $request): JsonResponse
    {
        $memberId = $this->getMemberId($request);
        $data = $request->validatedWithDefaults();

        try {
            $postId = $this->postService->createPost($memberId, $data);

            return AppApiResponse::success([
                'data' => ['post_id' => $postId]
            ]);
        } catch (\Exception $e) {
            Log::error('发表文章动态失败', [
                'member_id' => $memberId,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
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
     * 获取帖子列表-普通分页
     *
     * @param PostPageRequest $request
     * @return JsonResponse
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

            return AppApiResponse::postPaginate($posts, PostListResource::class);
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
     * 获取视频流列表（游标分页）- 用于刷视频场景
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function videoFeed(Request $request): JsonResponse
    {
        $cursor = $request->input('cursor');
        $pageSize = $request->input('pageSize', 10);
        $memberId = $this->getMemberId($request);

        try {
            $videos = $this->postService->getVideoFeed($cursor, $pageSize);

            // 如果用户已登录，获取交互状态
            if ($memberId) {
                $postIds = $videos->pluck('post_id')->toArray();
                $memberIds = $videos->pluck('member_id')->unique()->toArray();

                $collectedPostIds = $this->postService->getCollectedPostIds($memberId, $postIds);
                $likedPostIds = $this->postService->getLikedPostIds($memberId, $postIds);
                $followedMemberIds = $this->postService->getFollowedMemberIds($memberId, $memberIds);

                VideoFeedResource::setCollectedPostIds($collectedPostIds);
                VideoFeedResource::setLikedPostIds($likedPostIds);
                VideoFeedResource::setFollowedMemberIds($followedMemberIds);
            } else {
                VideoFeedResource::setCollectedPostIds([]);
                VideoFeedResource::setLikedPostIds([]);
                VideoFeedResource::setFollowedMemberIds([]);
            }

            return AppApiResponse::cursorPaginate($videos, VideoFeedResource::class);
        } catch (\Exception $e) {
            Log::error('获取视频流失败', [
                'cursor' => $cursor,
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
     * 获取图文动态详情
     *
     * @param Request $request
     * @param int $id 帖子ID
     * @return JsonResponse
     */
    public function detailImageText(Request $request, int $id): JsonResponse
    {
        $memberId = $this->getMemberId($request);

        try {
            $post = $this->postService->getPostDetailByType($id, AppPostBase::POST_TYPE_IMAGE_TEXT);

            if (!$post) {
                return AppApiResponse::dataNotFound('内容不存在');
            }

            $this->postService->incrementViewCount($post);

            $isCollected = false;
            $isLiked = false;
            if ($memberId) {
                $isCollected = $this->postService->isPostCollected($memberId, $id);
                $isLiked = $this->postService->isPostLiked($memberId, $id);
            }

            return AppApiResponse::resource(
                $post,
                ImageTextPostResource::class,
                'success',
                ['isCollected' => $isCollected, 'isLiked' => $isLiked]
            );
        } catch (\Exception $e) {
            Log::error('获取图文动态详情失败', [
                'post_id' => $id,
                'error' => $e->getMessage()
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 获取视频动态详情
     *
     * @param Request $request
     * @param int $id 帖子ID
     * @return JsonResponse
     */
    public function detailVideo(Request $request, int $id): JsonResponse
    {
        $memberId = $this->getMemberId($request);

        try {
            $post = $this->postService->getPostDetailByType($id, AppPostBase::POST_TYPE_VIDEO);

            if (!$post) {
                return AppApiResponse::dataNotFound('内容不存在');
            }

            $this->postService->incrementViewCount($post);

            $isCollected = false;
            $isLiked = false;
            if ($memberId) {
                $isCollected = $this->postService->isPostCollected($memberId, $id);
                $isLiked = $this->postService->isPostLiked($memberId, $id);
            }

            return AppApiResponse::resource(
                $post,
                VideoPostResource::class,
                'success',
                ['isCollected' => $isCollected, 'isLiked' => $isLiked]
            );
        } catch (\Exception $e) {
            Log::error('获取视频动态详情失败', [
                'post_id' => $id,
                'error' => $e->getMessage()
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 获取文章动态详情
     *
     * @param Request $request
     * @param int $id 帖子ID
     * @return JsonResponse
     */
    public function detailArticle(Request $request, int $id): JsonResponse
    {
        $memberId = $this->getMemberId($request);

        try {
            $post = $this->postService->getPostDetailByType($id, AppPostBase::POST_TYPE_ARTICLE);

            if (!$post) {
                return AppApiResponse::dataNotFound('内容不存在');
            }

            $this->postService->incrementViewCount($post);

            $isCollected = false;
            $isLiked = false;
            if ($memberId) {
                $isCollected = $this->postService->isPostCollected($memberId, $id);
                $isLiked = $this->postService->isPostLiked($memberId, $id);
            }

            return AppApiResponse::resource(
                $post,
                ArticlePostResource::class,
                'success',
                ['isCollected' => $isCollected, 'isLiked' => $isLiked]
            );
        } catch (\Exception $e) {
            Log::error('获取文章动态详情失败', [
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
     * @return JsonResponse
     */
    public function collect(Request $request)
    {
        $memberId = $this->getMemberId($request);
        $id = $request->get('postId', 0);

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
     * @return JsonResponse
     */
    public function uncollect(Request $request)
    {
        $memberId = $this->getMemberId($request);
        $id = $request->get('postId', 0);

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
     * @return JsonResponse
     */
    public function like(Request $request)
    {
        $memberId = $this->getMemberId($request);
        $id = $request->get('postId', 0);

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
     * @return JsonResponse
     */
    public function unlike(Request $request)
    {
        $memberId = $this->getMemberId($request);
        $id = $request->get('postId', 0);

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
