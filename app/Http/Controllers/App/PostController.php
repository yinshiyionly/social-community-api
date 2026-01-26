<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\App\PostListRequest;
use App\Http\Resources\App\AppApiResponse;
use App\Http\Resources\App\PostListResource;
use App\Services\App\PostService;
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
     * 获取帖子列表
     */
    public function list(PostListRequest $request)
    {
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 10);

        try {
            $posts = $this->postService->getPostList($page, $pageSize);

            return AppApiResponse::collection($posts, PostListResource::class);
        } catch (\Exception $e) {
            Log::error('获取帖子列表失败', [
                'page' => $page,
                'pageSize' => $pageSize,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return AppApiResponse::serverError();
        }
    }
}
