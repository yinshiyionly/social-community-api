<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\App\ArticlePostBlocksUpdateRequest;
use App\Http\Requests\App\ArticlePostBlocksStoreRequest;
use App\Http\Requests\App\ImageTextPostUpdateRequest;
use App\Http\Requests\App\ImageTextPostStoreRequest;
use App\Http\Requests\App\PostDeleteRequest;
use App\Http\Requests\App\PostDetailRequest;
use App\Http\Requests\App\PostListRequest;
use App\Http\Requests\App\PostPageRequest;
use App\Http\Requests\App\PostStoreRequest;
use App\Http\Requests\App\VideoPostUpdateRequest;
use App\Http\Requests\App\VideoPostStoreRequest;
use App\Http\Resources\App\AppApiResponse;
use App\Http\Resources\App\ArticlePostResource;
use App\Http\Resources\App\ImageTextPostResource;
use App\Http\Resources\App\PostDetailResource;
use App\Http\Resources\App\PostListResource;
use App\Http\Resources\App\VideoFeedResource;
use App\Http\Resources\App\VideoPostResource;
use App\Models\App\AppPostBase;
use App\Services\App\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * App 端帖子控制器。
 *
 * 职责：
 * 1. 提供帖子列表、详情、发布、删除等入口；
 * 2. 提供帖子点赞/收藏交互入口；
 * 3. 统一捕获异常并返回 AppApiResponse，避免暴露内部异常细节。
 */
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
                'data' => [
                    'postId' => $postId
                ]
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
                'data' => [
                    'postId' => $postId
                ]
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
     * @param ArticlePostBlocksStoreRequest $request
     * @return JsonResponse
     */
    public function storeArticle(ArticlePostBlocksStoreRequest $request): JsonResponse
    {
        $memberId = $this->getMemberId($request);
        $data = $request->validatedWithDefaults();

        try {
            $postId = $this->postService->createPost($memberId, $data);

            return AppApiResponse::success([
                'data' => ['postId' => $postId]
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
     * 更新图文动态（仅作者可更新）。
     *
     * 接口用途：
     * - 图文详情页作者编辑并全量覆盖提交图文帖子内容。
     *
     * @param ImageTextPostUpdateRequest $request
     * @return JsonResponse
     */
    public function updateImageText(ImageTextPostUpdateRequest $request): JsonResponse
    {
        $data = $request->validatedWithDefaults();

        return $this->handleUpdatePost(
            $request,
            $data,
            AppPostBase::POST_TYPE_IMAGE_TEXT,
            '更新图文动态失败'
        );
    }

    /**
     * 更新视频动态（仅作者可更新）。
     *
     * 接口用途：
     * - 视频详情页作者编辑并全量覆盖提交视频帖子内容。
     *
     * @param VideoPostUpdateRequest $request
     * @return JsonResponse
     */
    public function updateVideo(VideoPostUpdateRequest $request): JsonResponse
    {
        $data = $request->validatedWithDefaults();

        return $this->handleUpdatePost(
            $request,
            $data,
            AppPostBase::POST_TYPE_VIDEO,
            '更新视频动态失败'
        );
    }

    /**
     * 更新文章动态（仅作者可更新）。
     *
     * 接口用途：
     * - 文章详情页作者编辑并全量覆盖提交文章帖子内容。
     *
     * @param ArticlePostBlocksUpdateRequest $request
     * @return JsonResponse
     */
    public function updateArticle(ArticlePostBlocksUpdateRequest $request): JsonResponse
    {
        $data = $request->validatedWithDefaults();

        return $this->handleUpdatePost(
            $request,
            $data,
            AppPostBase::POST_TYPE_ARTICLE,
            '更新文章动态失败'
        );
    }

    /**
     * 统一处理“按类型更新帖子”响应。
     *
     * 关键规则：
     * 1. 仅允许作者更新，非作者返回 403；
     * 2. 帖子不存在或类型不匹配返回 404；
     * 3. 其余异常统一记录日志并返回通用错误，避免暴露内部实现细节。
     *
     * @param Request $request
     * @param array<string, mixed> $data
     * @param int $postType 帖子类型
     * @param string $errorLogMessage 异常日志文案
     * @return JsonResponse
     */
    protected function handleUpdatePost(
        Request $request,
        array $data,
        int $postType,
        string $errorLogMessage
    ): JsonResponse {
        $memberId = (int)$this->getMemberId($request);
        $postId = (int)($data['postId'] ?? 0);

        try {
            $result = $this->postService->updatePostByOwner($memberId, $postId, $postType, $data);

            if (!$result['success'] && $result['message'] === 'not_found') {
                return AppApiResponse::dataNotFound('内容不存在');
            }

            if (!$result['success'] && $result['message'] === 'forbidden') {
                return AppApiResponse::forbidden('无权更新该帖子');
            }

            return AppApiResponse::success([
                'data' => [
                    'postId' => $postId,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error($errorLogMessage, [
                'member_id' => $memberId,
                'post_id' => $postId,
                'post_type' => $postType,
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
            PostListResource::setCurrentMemberId($memberId ? (int)$memberId : 0);

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
            PostListResource::setCurrentMemberId($memberId ? (int)$memberId : 0);

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
            VideoFeedResource::setCurrentMemberId($memberId ? (int)$memberId : 0);

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
     * 获取帖子详情（v1）
     *
     * GET /api/app/v1/post/detail?postId=xxx&postType=1|2|3
     *
     * @param PostDetailRequest $request
     * @return JsonResponse
     */
    public function detail(PostDetailRequest $request): JsonResponse
    {
        $postId = (int)$request->input('postId');
        $postType = $this->normalizePostType($request->input('postType'));

        return $this->buildDetailResponse($request, $postId, $postType);
    }

    /**
     * 获取帖子详情（兼容旧版路径参数）
     *
     * GET /api/app/v1/post/detail/{id}
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function detailById(Request $request, int $id): JsonResponse
    {
        $postType = $this->normalizePostType($request->input('postType'));

        return $this->buildDetailResponse($request, $id, $postType);
    }

    /**
     * 归一化帖子类型参数
     *
     * @param mixed $postType
     * @return int|null
     */
    protected function normalizePostType($postType): ?int
    {
        if ($postType === null || $postType === '') {
            return null;
        }

        $normalized = (int)$postType;
        if (!in_array($normalized, [
            AppPostBase::POST_TYPE_IMAGE_TEXT,
            AppPostBase::POST_TYPE_VIDEO,
            AppPostBase::POST_TYPE_ARTICLE,
        ], true)) {
            return null;
        }

        return $normalized;
    }

    /**
     * 组装帖子详情响应
     *
     * 返回约定：
     * - isOwned 按“当前登录用户 member_id 是否等于帖子作者 member_id”计算；
     * - 可选鉴权下未登录时 isOwned 固定为 false。
     *
     * @param Request $request
     * @param int $postId
     * @param int|null $postType
     * @return JsonResponse
     */
    protected function buildDetailResponse(Request $request, int $postId, ?int $postType = null): JsonResponse
    {
        $memberId = $this->getMemberId($request);

        try {
            $post = is_null($postType)
                ? $this->postService->getPostDetail($postId)
                : $this->postService->getPostDetailByType($postId, $postType);

            if (!$post) {
                return AppApiResponse::dataNotFound('内容不存在');
            }

            // 增加浏览量
            $this->postService->incrementViewCount($post);

            $isFavorited = false;
            $isLiked = false;
            $isFollowed = false;
            $isOwned = false;
            if ($memberId) {
                $memberId = (int)$memberId;
                $isFavorited = $this->postService->isPostCollected($memberId, $postId);
                $isLiked = $this->postService->isPostLiked($memberId, $postId);
                $followedMemberIds = $this->postService->getFollowedMemberIds($memberId, [(int)$post->member_id]);
                $isFollowed = in_array((int)$post->member_id, $followedMemberIds, true);
                $isOwned = (int)$post->member_id === $memberId;
            }

            return AppApiResponse::resource(
                $post,
                PostDetailResource::class,
                'success',
                [
                    'isFollowed' => $isFollowed,
                    'isOwned' => $isOwned,
                    'isLiked' => $isLiked,
                    'isFavorited' => $isFavorited,
                ]
            );
        } catch (\Exception $e) {
            Log::error('获取帖子详情失败', [
                'post_id' => $postId,
                'post_type' => $postType,
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

            $isFavorited = false;
            $isLiked = false;
            $isOwned = false;
            if ($memberId) {
                $currentMemberId = (int)$memberId;
                $isFavorited = $this->postService->isPostCollected($currentMemberId, $id);
                $isLiked = $this->postService->isPostLiked($currentMemberId, $id);
                $isOwned = (int)$post->member_id === $currentMemberId;
            }

            return AppApiResponse::resource(
                $post,
                ImageTextPostResource::class,
                'success',
                [
                    'isOwned' => $isOwned,
                    'isLiked' => $isLiked,
                    'isFavorited' => $isFavorited,
                    // 兼容旧字段，避免历史客户端只读取 isCollected 时行为异常。
                    'isCollected' => $isFavorited,
                ]
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

            $isFavorited = false;
            $isLiked = false;
            $isOwned = false;
            if ($memberId) {
                $currentMemberId = (int)$memberId;
                $isFavorited = $this->postService->isPostCollected($currentMemberId, $id);
                $isLiked = $this->postService->isPostLiked($currentMemberId, $id);
                $isOwned = (int)$post->member_id === $currentMemberId;
            }

            return AppApiResponse::resource(
                $post,
                VideoPostResource::class,
                'success',
                [
                    'isOwned' => $isOwned,
                    'isLiked' => $isLiked,
                    'isFavorited' => $isFavorited,
                    // 兼容旧字段，避免历史客户端只读取 isCollected 时行为异常。
                    'isCollected' => $isFavorited,
                ]
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

            // 文章详情需要直接返回话题标签，提前加载避免资源层出现隐式查询。
            $post->load('topics');

            $this->postService->incrementViewCount($post);

            $isFavorited = false;
            $isLiked = false;
            $isOwned = false;
            if ($memberId) {
                $currentMemberId = (int)$memberId;
                $isFavorited = $this->postService->isPostCollected($currentMemberId, $id);
                $isLiked = $this->postService->isPostLiked($currentMemberId, $id);
                $isOwned = (int)$post->member_id === $currentMemberId;
            }

            return AppApiResponse::resource(
                $post,
                ArticlePostResource::class,
                'success',
                [
                    'isOwned' => $isOwned,
                    'isLiked' => $isLiked,
                    'isFavorited' => $isFavorited,
                ]
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
     * 删除帖子（仅作者可删除）。
     *
     * 接口用途：
     * - 图文/视频/文章详情页作者删除入口。
     *
     * 关键输入：
     * - postId：帖子 ID；
     * - postType：可选，传入时参与精确匹配，防止误删同 ID 异类型数据。
     *
     * 关键输出：
     * - 成功统一返回 data.postId + data.deleted=true；
     * - 重复删除按幂等成功处理，保证前端重复点击不报错。
     *
     * 失败分支：
     * - 帖子不存在返回 404；
     * - 非作者删除返回 403；
     * - 其他异常记录日志后返回通用错误，避免泄露内部实现细节。
     *
     * @param PostDeleteRequest $request
     * @return JsonResponse
     */
    public function delete(PostDeleteRequest $request): JsonResponse
    {
        $memberId = (int)$this->getMemberId($request);
        $postId = (int)$request->input('postId');
        $postType = $request->filled('postType') ? (int)$request->input('postType') : null;

        try {
            $result = $this->postService->deletePostByOwner($memberId, $postId, $postType);

            if (!$result['success'] && $result['message'] === 'not_found') {
                return AppApiResponse::dataNotFound('内容不存在');
            }

            if (!$result['success'] && $result['message'] === 'forbidden') {
                return AppApiResponse::forbidden('无权删除该帖子');
            }

            return AppApiResponse::success([
                'data' => [
                    'postId' => $postId,
                    'deleted' => true,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('删除帖子失败', [
                'member_id' => $memberId,
                'post_id' => $postId,
                'post_type' => $postType,
                'error' => $e->getMessage(),
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
