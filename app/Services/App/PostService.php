<?php

namespace App\Services\App;

use App\Models\App\AppPostBase;
use App\Models\App\AppPostCollection;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Facades\DB;

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

    /**
     * 收藏帖子
     *
     * @param int $memberId 会员ID
     * @param int $postId 帖子ID
     * @return array ['success' => bool, 'message' => string, 'is_collected' => bool]
     */
    public function collectPost(int $memberId, int $postId): array
    {
        // 检查帖子是否存在且可访问
        $post = AppPostBase::query()
            ->approved()
            ->visible()
            ->where('post_id', $postId)
            ->first();

        if (!$post) {
            return [
                'success' => false,
                'message' => 'not_found',
                'is_collected' => false,
            ];
        }

        // 检查是否已收藏
        if (AppPostCollection::isCollected($memberId, $postId)) {
            return [
                'success' => true,
                'message' => 'already_collected',
                'is_collected' => true,
            ];
        }

        try {
            DB::beginTransaction();

            // 创建收藏记录
            AppPostCollection::create([
                'member_id' => $memberId,
                'post_id' => $postId,
            ]);

            // 增加帖子收藏数
            $post->incrementCollectionCount();

            DB::commit();

            return [
                'success' => true,
                'message' => 'collected',
                'is_collected' => true,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 取消收藏帖子
     *
     * @param int $memberId 会员ID
     * @param int $postId 帖子ID
     * @return array ['success' => bool, 'message' => string, 'is_collected' => bool]
     */
    public function uncollectPost(int $memberId, int $postId): array
    {
        // 检查帖子是否存在
        $post = AppPostBase::query()
            ->where('post_id', $postId)
            ->first();

        if (!$post) {
            return [
                'success' => false,
                'message' => 'not_found',
                'is_collected' => false,
            ];
        }

        // 查找收藏记录
        $collection = AppPostCollection::where('member_id', $memberId)
            ->where('post_id', $postId)
            ->first();

        if (!$collection) {
            return [
                'success' => true,
                'message' => 'not_collected',
                'is_collected' => false,
            ];
        }

        try {
            DB::beginTransaction();

            // 删除收藏记录
            $collection->delete();

            // 减少帖子收藏数
            $post->decrementCollectionCount();

            DB::commit();

            return [
                'success' => true,
                'message' => 'uncollected',
                'is_collected' => false,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 检查帖子是否已被收藏
     *
     * @param int $memberId 会员ID
     * @param int $postId 帖子ID
     * @return bool
     */
    public function isPostCollected(int $memberId, int $postId): bool
    {
        return AppPostCollection::isCollected($memberId, $postId);
    }

    /**
     * 批量检查帖子是否已被收藏
     *
     * @param int $memberId 会员ID
     * @param array $postIds 帖子ID数组
     * @return array 已收藏的帖子ID数组
     */
    public function getCollectedPostIds(int $memberId, array $postIds): array
    {
        return AppPostCollection::getCollectedPostIds($memberId, $postIds);
    }
}
