<?php

namespace App\Services\App;

use App\Models\App\AppMemberBase;
use App\Models\App\AppMemberFollow;
use App\Models\App\AppPostBase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

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
     * 静态推荐用户数据
     */
    private const RECOMMEND_MEMBERS = [
        [
            'member_id' => 1001,
            'nickname' => '官方小助手',
            'avatar' => 'https://example.com/avatars/assistant.png',
            'bio' => '欢迎来到社区，有问题随时找我~',
        ],
        [
            'member_id' => 1002,
            'nickname' => '生活达人',
            'avatar' => 'https://example.com/avatars/lifestyle.png',
            'bio' => '分享生活中的美好瞬间',
        ],
        [
            'member_id' => 1003,
            'nickname' => '美食探店',
            'avatar' => 'https://example.com/avatars/food.png',
            'bio' => '带你发现城市里的美味',
        ],
    ];


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
     * @return Collection
     */
    public function getRecommendMembers(): Collection
    {
        return collect(self::RECOMMEND_MEMBERS);
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

        // 查找现有关注记录
        $follow = AppMemberFollow::query()
            ->byMember($memberId)
            ->byFollowMember($followMemberId)
            ->first();

        if ($follow) {
            // 更新状态为正常
            $follow->status = AppMemberFollow::STATUS_NORMAL;
            $follow->source = $source;
            $follow->save();
        } else {
            // 创建新记录
            AppMemberFollow::create([
                'member_id' => $memberId,
                'follow_member_id' => $followMemberId,
                'source' => $source,
                'status' => AppMemberFollow::STATUS_NORMAL,
            ]);
        }

        return [
            'success' => true,
            'message' => 'followed',
            'is_following' => true,
        ];
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

        // 更新状态为已取消
        $follow->status = AppMemberFollow::STATUS_CANCELLED;
        $follow->save();

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
     * @return LengthAwarePaginator
     */
    public function getFollowingPosts(int $memberId, int $page, int $pageSize): LengthAwarePaginator
    {
        // 获取关注的用户ID列表
        $followingIds = AppMemberFollow::query()
            ->byMember($memberId)
            ->normal()
            ->pluck('follow_member_id')
            ->toArray();

        // 如果没有关注任何人，返回空分页
        if (empty($followingIds)) {
            return new LengthAwarePaginator([], 0, $pageSize, $page);
        }

        // 查询这些用户的帖子
        return AppPostBase::query()
            ->select(self::POST_LIST_COLUMNS)
            ->whereIn('member_id', $followingIds)
            ->approved()
            ->visible()
            ->with(self::MEMBER_COLUMNS)
            ->orderByDesc('created_at')
            ->paginate($pageSize, ['*'], 'page', $page);
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
