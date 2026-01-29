<?php

namespace App\Services\App;

use App\Models\App\AppMemberBase;
use App\Models\App\AppMemberFollow;
use App\Models\App\AppPostBase;
use App\Models\App\AppPostCollection;
use App\Models\App\AppPostLike;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FollowService
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
     * 会员关联查询字段
     */
    private const MEMBER_COLUMNS = 'member:member_id,nickname,avatar';

    /**
     * 推荐用户ID列表
     */
    private const RECOMMEND_MEMBER_IDS = [3545623190, 3545623191, 3545623192];


    /**
     * 获取关注列表
     *
     * @param int $memberId 当前用户ID
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @return LengthAwarePaginator
     */
    public function getFollowingList(int $memberId, int $page, int $pageSize): LengthAwarePaginator
    {
        return AppMemberFollow::query()
            ->byMember($memberId)
            ->normal()
            ->with('followMember:member_id,nickname,avatar')
            ->orderByDesc('created_at')
            ->paginate($pageSize, ['*'], 'page', $page);
    }

    /**
     * 获取推荐用户列表
     *
     * @param int $memberId 当前用户ID
     * @return Collection
     */
    public function getRecommendMembers(int $memberId): Collection
    {
        if (empty(self::RECOMMEND_MEMBER_IDS)) {
            return collect([]);
        }

        // 查询推荐用户基础信息（直接使用冗余的 fans_count）
        $members = AppMemberBase::query()
            ->whereIn('member_id', self::RECOMMEND_MEMBER_IDS)
            ->select(['member_id', 'nickname', 'avatar', 'bio', 'fans_count'])
            ->get();

        if ($members->isEmpty()) {
            return collect([]);
        }

        $memberIds = $members->pluck('member_id')->toArray();

        // 批量查询当前用户是否已关注
        $followedIds = AppMemberFollow::query()
            ->byMember($memberId)
            ->whereIn('follow_member_id', $memberIds)
            ->normal()
            ->pluck('follow_member_id')
            ->toArray();

        // 组装数据
        return $members->map(function ($member) use ($followedIds) {
            return [
                'member_id' => $member->member_id,
                'nickname' => $member->nickname,
                'avatar' => $member->avatar,
                'bio' => $member->bio,
                'fans_count' => $member->fans_count,
                'fans_count_text' => $this->formatFansCount($member->fans_count),
                'is_followed' => in_array($member->member_id, $followedIds),
            ];
        });
    }

    /**
     * 格式化粉丝数显示
     *
     * @param int $count
     * @return string
     */
    public function formatFansCount(int $count): string
    {
        if ($count >= 10000) {
            return round($count / 10000, 1) . 'w粉丝';
        }
        return $count . '粉丝';
    }

    /**
     * 关注用户
     *
     * @param int $memberId 当前用户ID
     * @param int $followMemberId 被关注用户ID
     * @param string $source 关注来源
     * @return array ['success' => bool, 'message' => string, 'is_following' => bool]
     */
    public function followMember(int $memberId, int $followMemberId, string $source): array
    {
        // 检查是否关注自己
        if ($memberId === $followMemberId) {
            return [
                'success' => false,
                'message' => 'self_follow',
                'is_following' => false,
            ];
        }

        // 检查被关注用户是否存在
        if (!$this->memberExists($followMemberId)) {
            return [
                'success' => false,
                'message' => 'not_found',
                'is_following' => false,
            ];
        }

        DB::beginTransaction();
        try {
            // 查找现有关注记录
            $follow = AppMemberFollow::query()
                ->byMember($memberId)
                ->byFollowMember($followMemberId)
                ->first();

            $shouldUpdateCount = false;

            if ($follow) {
                // 只有从非正常状态变为正常状态才更新计数
                if ($follow->status !== AppMemberFollow::STATUS_NORMAL) {
                    $shouldUpdateCount = true;
                    $follow->status = AppMemberFollow::STATUS_NORMAL;
                    $follow->source = $source;
                    $follow->save();
                }
            } else {
                // 创建新记录
                AppMemberFollow::create([
                    'member_id' => $memberId,
                    'follow_member_id' => $followMemberId,
                    'source' => $source,
                    'status' => AppMemberFollow::STATUS_NORMAL,
                ]);
                $shouldUpdateCount = true;
            }

            // 更新冗余计数
            if ($shouldUpdateCount) {
                // 增加被关注者的粉丝数
                AppMemberBase::query()
                    ->where('member_id', $followMemberId)
                    ->increment('fans_count');
                // 增加当前用户的关注数
                AppMemberBase::query()
                    ->where('member_id', $memberId)
                    ->increment('following_count');
            }

            DB::commit();

            // 创建关注消息（仅当实际更新了计数时才发送）
            if ($shouldUpdateCount) {
                MessageService::createFollowMessage($memberId, $followMemberId);
            }

            return [
                'success' => true,
                'message' => 'followed',
                'is_following' => true,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }


    /**
     * 取消关注
     *
     * @param int $memberId 当前用户ID
     * @param int $followMemberId 被关注用户ID
     * @return array ['success' => bool, 'message' => string, 'is_following' => bool]
     */
    public function unfollowMember(int $memberId, int $followMemberId): array
    {
        // 查找关注记录
        $follow = AppMemberFollow::query()
            ->byMember($memberId)
            ->byFollowMember($followMemberId)
            ->first();

        if (!$follow) {
            return [
                'success' => true,
                'message' => 'not_following',
                'is_following' => false,
            ];
        }

        // 只有当前是正常关注状态才更新计数
        if ($follow->status === AppMemberFollow::STATUS_NORMAL) {
            DB::beginTransaction();
            try {
                // 更新状态为已取消
                $follow->status = AppMemberFollow::STATUS_CANCELLED;
                $follow->save();

                // 减少被关注者的粉丝数
                AppMemberBase::query()
                    ->where('member_id', $followMemberId)
                    ->where('fans_count', '>', 0)
                    ->decrement('fans_count');
                // 减少当前用户的关注数
                AppMemberBase::query()
                    ->where('member_id', $memberId)
                    ->where('following_count', '>', 0)
                    ->decrement('following_count');

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        }

        return [
            'success' => true,
            'message' => 'unfollowed',
            'is_following' => false,
        ];
    }

    /**
     * 获取关注用户的帖子列表
     *
     * @param int $memberId 当前用户ID
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @return array ['posts' => LengthAwarePaginator, 'likedIds' => array, 'collectedIds' => array, 'followedIds' => array]
     */
    public function getFollowingPosts(int $memberId, int $page, int $pageSize): array
    {
        // 获取关注的用户ID列表
        $followingIds = AppMemberFollow::query()
            ->byMember($memberId)
            ->normal()
            ->pluck('follow_member_id')
            ->toArray();

        // 如果没有关注任何人，返回空分页
        if (empty($followingIds)) {
            return [
                'posts' => new LengthAwarePaginator([], 0, $pageSize, $page),
                'likedIds' => [],
                'collectedIds' => [],
                'followedIds' => [],
            ];
        }

        // 查询这些用户的帖子
        $posts = AppPostBase::query()
            ->select(self::POST_LIST_COLUMNS)
            ->whereIn('member_id', $followingIds)
            ->approved()
            ->visible()
            ->with(self::MEMBER_COLUMNS)
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

    /**
     * 检查是否已关注
     *
     * @param int $memberId 当前用户ID
     * @param int $followMemberId 被关注用户ID
     * @return bool
     */
    public function isFollowing(int $memberId, int $followMemberId): bool
    {
        return AppMemberFollow::query()
            ->byMember($memberId)
            ->byFollowMember($followMemberId)
            ->normal()
            ->exists();
    }

    /**
     * 检查用户是否存在
     *
     * @param int $memberId 用户ID
     * @return bool
     */
    public function memberExists(int $memberId): bool
    {
        return AppMemberBase::query()
            ->where('member_id', $memberId)
            ->exists();
    }
}
