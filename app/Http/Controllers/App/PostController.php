<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\App\PostListRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\App\PostResource;
use App\Services\App\PostService;

class PostController extends Controller
{
    public function __construct(
        protected PostService $postService
    ) {}

    /**
     * 动态列表（游标分页）
     */
    public function list(PostListRequest $request)
    {
        $result = $this->postService->getList(
            cursor: $request->input('cursor'),
            limit: $request->input('limit', 20),
            memberId: $request->input('member_id'),
            postType: $request->input('post_type')
        );

        return ApiResponse::success([
            'list' => PostResource::collection($result['list']),
            'next_cursor' => $result['next_cursor'],
            'has_more' => $result['has_more'],
        ]);
    }
}
