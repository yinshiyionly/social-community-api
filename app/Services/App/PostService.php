<?php

namespace App\Services\App;

use App\Models\App\AppPostBase;
use Illuminate\Support\Collection;

class PostService
{
    /**
     * 获取帖子列表
     *
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @return Collection
     */
    public function getPostList(int $page = 1, int $pageSize = 10): Collection
    {
        $offset = ($page - 1) * $pageSize;

        return AppPostBase::query()
            ->with('member')
            ->approved()
            ->visible()
            ->orderByTop()
            ->offset($offset)
            ->limit($pageSize)
            ->get();
    }
}
