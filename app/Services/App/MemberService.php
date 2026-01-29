<?php

namespace App\Services\App;

use App\Models\App\AppMemberBase;
use App\Models\App\AppMemberFollow;
use App\Models\App\AppPostBase;
use App\Models\App\AppPostCollection;

class MemberService
{
    /**
     * 会员主页查询字段
     */
    private const PROFILE_COLUMNS = [
        'member_id',
        'nickname',
        'avatar',
        'bio',
        'fans_count',
        'following_count',
        'like_count',
    ];

    /**
     * 获取用户主页信息
     *
     * @param int $memberId 目标用户ID
     * @return AppMemberBase|null
     */
    public function getMemberProfile(int $memberId)
    {
        return AppMemberBase::query()
            ->select(self::PROFILE_COLUMNS)
            ->normal()
            ->where('member_id', $memberId)
            ->first();
    }

    /**
     * 获取用户帖子总数
     *
     * @param int $memberId 用户ID
     * @return int
     */
    public function getMemberPostCount(int $memberId): int
    {
        return AppPostBase::query()
            ->byMember($memberId)
            ->approved()
            ->visible()
            ->count();
    }

    /**
     * 检查是否已关注
     *
     * @param int $memberId 当前用户ID
     * @param int $targetMemberId 目标用户ID
     * @return bool
     */
    public function isFollowing(int $memberId, int $targetMemberId): bool
    {
        return AppMemberFollow::query()
            ->byMember($memberId)
            ->byFollowMember($targetMemberId)
            ->normal()
            ->exists();
    }

    /**
     * 帖子列表查询字段
     */
    private const POST_LIST_COLUMNS = [
        'post_id',
        'member_id',
        'post_type',
        'title',
        'content',
        'cover',
        'like_count',
        'created_at',
    ];

    /**
     * 获取用户帖子列表（普通分页）
     *
     * @param int $memberId 用户ID
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getMemberPosts(int $memberId, int $page = 1, int $pageSize = 10)
    {
        return AppPostBase::query()
            ->select(self::POST_LIST_COLUMNS)
            ->byMember($memberId)
            ->approved()
            ->visible()
            ->orderByDesc('post_id')
            ->paginate($pageSize, ['*'], 'page', $page);
    }

    /**
     * 获取用户收藏帖子列表（普通分页）
     *
     * @param int $memberId 用户ID
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getMemberCollections(int $memberId, int $page = 1, int $pageSize = 10)
    {
        return AppPostCollection::query()
            ->select(['collection_id', 'post_id', 'created_at'])
            ->with(['post' => function ($query) {
                $query->select(self::POST_LIST_COLUMNS)
                    ->approved()
                    ->visible();
            }])
            ->byMember($memberId)
            ->orderByDesc('collection_id')
            ->paginate($pageSize, ['*'], 'page', $page);
    }

    /**
     * 获取用户粉丝列表（普通分页）
     *
     * @param int $memberId 用户ID
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getMemberFans(int $memberId, int $page = 1, int $pageSize = 10)
    {
        return AppMemberFollow::query()
            ->select(['follow_id', 'member_id', 'created_at'])
            ->with(['member' => function ($query) {
                $query->select(['member_id', 'nickname', 'avatar', 'bio', 'fans_count'])
                    ->normal();
            }])
            ->byFollowMember($memberId)
            ->normal()
            ->orderByDesc('follow_id')
            ->paginate($pageSize, ['*'], 'page', $page);
    }

    /**
     * 批量获取当前用户对指定用户列表的关注状态
     *
     * @param int $memberId 当前用户ID
     * @param array $targetMemberIds 目标用户ID列表
     * @return array 已关注的用户ID列表
     */
    public function getFollowedMemberIds(int $memberId, array $targetMemberIds): array
    {
        if (empty($targetMemberIds)) {
            return [];
        }

        return AppMemberFollow::query()
            ->byMember($memberId)
            ->whereIn('follow_member_id', $targetMemberIds)
            ->normal()
            ->pluck('follow_member_id')
            ->toArray();
    }

    /**
     * 获取用户关注列表（普通分页）
     *
     * @param int $memberId 用户ID
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getMemberFollowings(int $memberId, int $page = 1, int $pageSize = 10)
    {
        return AppMemberFollow::query()
            ->select(['follow_id', 'follow_member_id', 'created_at'])
            ->with(['followMember' => function ($query) {
                $query->select(['member_id', 'nickname', 'avatar', 'bio', 'fans_count'])
                    ->normal();
            }])
            ->byMember($memberId)
            ->normal()
            ->orderByDesc('follow_id')
            ->paginate($pageSize, ['*'], 'page', $page);
    }

    /**
     * 更新用户头像
     *
     * @param int $memberId 用户ID
     * @param string $avatar 头像URL
     * @return bool
     */
    public function updateAvatar(int $memberId, string $avatar): bool
    {
        return AppMemberBase::query()
            ->where('member_id', $memberId)
            ->update(['avatar' => $avatar]) > 0;
    }

    /**
     * 更新用户昵称
     *
     * @param int $memberId 用户ID
     * @param string $nickname 昵称
     * @return bool
     */
    public function updateNickname(int $memberId, string $nickname): bool
    {
        return AppMemberBase::query()
            ->where('member_id', $memberId)
            ->update(['nickname' => $nickname]) > 0;
    }
}
