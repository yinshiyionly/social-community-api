<?php

namespace App\Services\App;

use App\Models\App\AppMemberFollow;
use App\Models\App\AppPostBase;
use App\Models\App\AppPostCollection;
use App\Models\App\AppPostLike;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * 推荐帖子服务类
 */
class RecommendPostService
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
        'cover',
        'image_show_style',
        'article_cover_style',
        'is_top',
        'sort_score',
        'created_at',
    ];

    /**
     * 会员关联查询字段
     */
    private const MEMBER_COLUMNS = 'member:member_id,nickname,avatar';

    /**
     * 统计关联
     */
    private const STAT_RELATION = 'stat:post_id,view_count,like_count,comment_count,share_count,collection_count';

    /**
     * 推荐帖子ID列表（写死）
     */
    private const RECOMMEND_POST_IDS = [
        350785364790,
        350785364791,
        350785364792,
        350785364794,
        350785364795,
        350785364796,
        350785364797
    ];

    /**
     * 获取推荐帖子列表（猜你喜欢）
     *
     * @param int $memberId 当前用户ID
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @return array ['posts' => LengthAwarePaginator, 'likedIds' => array, 'collectedIds' => array, 'followedIds' => array]
     */
    public function getRecommendPosts(int $memberId, int $page, int $pageSize): array
    {
        // 如果没有推荐帖子ID，返回空分页
        if (empty(self::RECOMMEND_POST_IDS)) {
            return [
                'posts' => new LengthAwarePaginator([], 0, $pageSize, $page),
                'likedIds' => [],
                'collectedIds' => [],
                'followedIds' => [],
            ];
        }

        // 查询推荐帖子
        $posts = AppPostBase::query()
            ->select(self::POST_LIST_COLUMNS)
            ->whereIn('post_id', self::RECOMMEND_POST_IDS)
            ->approved()
            ->visible()
            ->with([self::MEMBER_COLUMNS, self::STAT_RELATION])
            ->orderByDesc('created_at')
            ->paginate($pageSize, ['*'], 'page', $page);

        // 批量查询当前用户的交互状态
        $postIds = $posts->pluck('post_id')->toArray();
        $authorIds = $posts->pluck('member_id')->unique()->toArray();

        $likedIds = [];
        $collectedIds = [];
        $followedAuthorIds = [];

        if (!empty($postIds)) {
            $likedIds = AppPostLike::getLikedPostIds($memberId, $postIds);
            $collectedIds = AppPostCollection::getCollectedPostIds($memberId, $postIds);
        }

        if (!empty($authorIds)) {
            $followedAuthorIds = AppMemberFollow::query()
                ->byMember($memberId)
                ->whereIn('follow_member_id', $authorIds)
                ->normal()
                ->pluck('follow_member_id')
                ->toArray();
        }

        return [
            'posts' => $posts,
            'likedIds' => $likedIds,
            'collectedIds' => $collectedIds,
            'followedIds' => $followedAuthorIds,
        ];
    }
}
