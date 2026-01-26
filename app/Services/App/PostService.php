<?php

namespace App\Services\App;

use App\Models\App\AppPostBase;
use Illuminate\Pagination\CursorPaginator;

class PostService
{
    /**
     * 获取帖子列表（游标分页）
     *
     * @param string|null $cursor 游标
     * @param int $pageSize 每页数量
     * @return CursorPaginator
     */
    public function getPostList(?string $cursor = null, int $pageSize = 10): CursorPaginator
    {
        return AppPostBase::query()
            ->with('member')
            ->approved()
            ->visible()
            ->orderByDesc('is_top')
            ->orderByDesc('sort_score')
            ->orderByDesc('post_id')
            ->cursorPaginate($pageSize, ['*'], 'cursor', $cursor);
    }

    /**
     * 获取帖子详情
     *
     * @param int $postId 帖子ID
     * @return AppPostBase|null
     */
    public function getPostDetail(int $postId)
    {
        return AppPostBase::query()
            ->with('member')
            ->approved()
            ->visible()
            ->where('post_id', $postId)
            ->first();
    }

    /**
     * 增加帖子浏览量
     *
     * @param AppPostBase $post 帖子模型
     * @return bool
     */
    public function incrementViewCount(AppPostBase $post): bool
    {
        return $post->incrementViewCount();
    }
}
