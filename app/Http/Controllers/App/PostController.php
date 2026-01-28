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

    public function feed()
    {
        $json = <<<EOF
{
    "data": [
        {
            "id": 11,
            "cover": "https://picsum.photos/400/523?random=211",
            "title": "",
            "avatar": "/static/images/avatar.jpg",
            "nickname": "韩",
            "likes": 231,
            "isVideo": false,
            "aspectRatio": 1.1844174939052037
        },
        {
            "id": 12,
            "cover": "https://picsum.photos/400/520?random=212",
            "title": "零基础京剧唱腔营",
            "avatar": "/static/images/avatar.jpg",
            "nickname": "林",
            "likes": 227,
            "isVideo": true,
            "aspectRatio": 1.7590048998265542
        },
        {
            "id": 13,
            "cover": "https://picsum.photos/400/489?random=213",
            "title": "小寒温养瑜伽，通经升阳 温络固本，适合冬季练习的养生瑜伽动作分享，建议每天早晚各练习一次效果更佳",
            "avatar": "/static/images/avatar.jpg",
            "nickname": "蕾蕾",
            "likes": 228,
            "isVideo": false,
            "aspectRatio": 0.9553714517348583
        },
        {
            "id": 14,
            "cover": "https://picsum.photos/400/417?random=214",
            "title": "2025兴趣岛第四届手机摄影大赛正式开启报名！本次大赛设置多个主题赛道，丰厚奖品等你来拿",
            "avatar": "/static/images/avatar.jpg",
            "nickname": "小美",
            "likes": 107,
            "isVideo": false,
            "aspectRatio": 1.0168621293342923
        },
        {
            "id": 15,
            "cover": "https://picsum.photos/400/454?random=215",
            "title": "温度提高1°C，免疫力提高30%",
            "avatar": "/static/images/avatar.jpg",
            "nickname": "阿杰",
            "likes": 278,
            "isVideo": false,
            "aspectRatio": 1.4252494374575466
        },
        {
            "id": 16,
            "cover": "https://picsum.photos/400/393?random=216",
            "title": "",
            "avatar": "/static/images/avatar.jpg",
            "nickname": "云舒",
            "likes": 189,
            "isVideo": false,
            "aspectRatio": 1.0906631361643686
        },
        {
            "id": 17,
            "cover": "https://picsum.photos/400/411?random=217",
            "title": "蘑菇上的灵动",
            "avatar": "/static/images/avatar.jpg",
            "nickname": "张锋",
            "likes": 112,
            "isVideo": true,
            "aspectRatio": 1.7561708340446698
        },
        {
            "id": 18,
            "cover": "https://picsum.photos/400/487?random=218",
            "title": "",
            "avatar": "/static/images/avatar.jpg",
            "nickname": "珂",
            "likes": 70,
            "isVideo": false,
            "aspectRatio": 1.258329647339067
        },
        {
            "id": 19,
            "cover": "https://picsum.photos/400/544?random=219",
            "title": "",
            "avatar": "/static/images/avatar.jpg",
            "nickname": "韩",
            "likes": 333,
            "isVideo": false,
            "aspectRatio": 0.9726100173883867
        },
        {
            "id": 20,
            "cover": "https://picsum.photos/400/596?random=220",
            "title": "冬日早安",
            "avatar": "/static/images/avatar.jpg",
            "nickname": "林",
            "likes": 135,
            "isVideo": false,
            "aspectRatio": 1.7723276199544462
        }
    ]
}
EOF;
        $arr = json_decode($json, true);
        return AppApiResponse::success($arr);
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
