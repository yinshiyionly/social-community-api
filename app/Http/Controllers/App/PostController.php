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
     * 获取帖子列表（游标分页）
     */
    public function list(PostListRequest $request)
    {
        $cursor = $request->input('cursor');
        $pageSize = $request->input('pageSize', 10);

        try {
            $posts = $this->postService->getPostList($cursor, $pageSize);

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
}
