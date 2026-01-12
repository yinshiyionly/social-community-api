<?php

namespace App\Services\App;

use App\Models\App\AppPostBase;

class PostService
{
    /**
     * 获取动态列表（游标分页）
     */
    public function getList(
        ?int $cursor = null,
        int $limit = 20,
        ?int $memberId = null,
        ?int $postType = null
    ): array {
        $query = AppPostBase::query()
            ->approved()
            ->visible()
            ->orderByTop();

        // 游标条件
        if ($cursor !== null && $cursor > 0) {
            $query->where('post_id', '<', $cursor);
        }

        // 筛选条件
        if ($memberId !== null) {
            $query->byMember($memberId);
        }

        if ($postType !== null) {
            $query->byType($postType);
        }

        // 多取一条判断是否有下一页
        $posts = $query->limit($limit + 1)->get();

        $hasMore = $posts->count() > $limit;
        if ($hasMore) {
            $posts = $posts->slice(0, $limit);
        }

        $nextCursor = $hasMore && $posts->isNotEmpty()
            ? $posts->last()->post_id
            : null;

        return [
            'list' => $posts,
            'next_cursor' => $nextCursor,
            'has_more' => $hasMore,
        ];
    }
}
