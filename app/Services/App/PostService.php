<?php

namespace App\Services\App;

use App\Models\App\AppPostBase;
use App\Models\App\AppPostCollection;
use App\Models\App\AppPostLike;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Facades\DB;

class PostService
{
    /**
     * 帖子列表查询字段
     */
    private const POST_LIST_COLUMNS = [
        'post_id',
        'member_id',
        'post_type',
        'title',
        'content',
        'media_data',
        'location_name',
        'view_count',
        'like_count',
        'comment_count',
        'share_count',
        'collection_count',
        'is_top',
        'sort_score',
        'created_at',
    ];

    /**
     * 帖子详情查询字段
     */
    private const POST_DETAIL_COLUMNS = [
        'post_id',
        'member_id',
        'post_type',
        'title',
        'content',
        'media_data',
        'location_name',
        'location_geo',
        'view_count',
        'like_count',
        'comment_count',
        'share_count',
        'collection_count',
        'is_top',
        'created_at',
    ];

    /**
     * 会员关联查询字段
     */
    private const MEMBER_COLUMNS = 'member:member_id,nickname,avatar';

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
            ->select(self::POST_LIST_COLUMNS)
            ->with(self::MEMBER_COLUMNS)
            ->approved()
            ->visible()
            ->orderByDesc('is_top')
            ->orderByDesc('sort_score')
            ->orderByDesc('post_id')
            ->cursorPaginate($pageSize, ['*'], 'cursor', $cursor);
    }

    /**
     * 获取帖子列表（普通分页）
     *
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getPostListPaginate(int $page = 1, int $pageSize = 10)
    {
        return AppPostBase::query()
            ->select(self::POST_LIST_COLUMNS)
            ->with(self::MEMBER_COLUMNS)
            ->approved()
            ->visible()
            ->orderByDesc('is_top')
            ->orderByDesc('sort_score')
            ->orderByDesc('post_id')
            ->paginate($pageSize, ['*'], 'page', $page);
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
            ->select(self::POST_DETAIL_COLUMNS)
            ->with(self::MEMBER_COLUMNS)
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
            ->select(['post_id', 'collection_count'])
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
            ->select(['post_id', 'collection_count'])
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

    /**
     * 点赞帖子
     *
     * @param int $memberId 会员ID
     * @param int $postId 帖子ID
     * @return array ['success' => bool, 'message' => string, 'is_liked' => bool]
     */
    public function likePost(int $memberId, int $postId): array
    {
        // 检查帖子是否存在且可访问
        $post = AppPostBase::query()
            ->select(['post_id', 'like_count'])
            ->approved()
            ->visible()
            ->where('post_id', $postId)
            ->first();

        if (!$post) {
            return [
                'success' => false,
                'message' => 'not_found',
                'is_liked' => false,
            ];
        }

        // 检查是否已点赞
        if (AppPostLike::isLiked($memberId, $postId)) {
            return [
                'success' => true,
                'message' => 'already_liked',
                'is_liked' => true,
            ];
        }

        try {
            DB::beginTransaction();

            // 创建点赞记录
            AppPostLike::create([
                'member_id' => $memberId,
                'post_id' => $postId,
            ]);

            // 增加帖子点赞数
            $post->incrementLikeCount();

            DB::commit();

            return [
                'success' => true,
                'message' => 'liked',
                'is_liked' => true,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 取消点赞帖子
     *
     * @param int $memberId 会员ID
     * @param int $postId 帖子ID
     * @return array ['success' => bool, 'message' => string, 'is_liked' => bool]
     */
    public function unlikePost(int $memberId, int $postId): array
    {
        // 检查帖子是否存在
        $post = AppPostBase::query()
            ->select(['post_id', 'like_count'])
            ->where('post_id', $postId)
            ->first();

        if (!$post) {
            return [
                'success' => false,
                'message' => 'not_found',
                'is_liked' => false,
            ];
        }

        // 查找点赞记录
        $like = AppPostLike::where('member_id', $memberId)
            ->where('post_id', $postId)
            ->first();

        if (!$like) {
            return [
                'success' => true,
                'message' => 'not_liked',
                'is_liked' => false,
            ];
        }

        try {
            DB::beginTransaction();

            // 删除点赞记录
            $like->delete();

            // 减少帖子点赞数
            $post->decrementLikeCount();

            DB::commit();

            return [
                'success' => true,
                'message' => 'unliked',
                'is_liked' => false,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 检查帖子是否已被点赞
     *
     * @param int $memberId 会员ID
     * @param int $postId 帖子ID
     * @return bool
     */
    public function isPostLiked(int $memberId, int $postId): bool
    {
        return AppPostLike::isLiked($memberId, $postId);
    }

    /**
     * 批量检查帖子是否已被点赞
     *
     * @param int $memberId 会员ID
     * @param array $postIds 帖子ID数组
     * @return array 已点赞的帖子ID数组
     */
    public function getLikedPostIds(int $memberId, array $postIds): array
    {
        return AppPostLike::getLikedPostIds($memberId, $postIds);
    }
}
