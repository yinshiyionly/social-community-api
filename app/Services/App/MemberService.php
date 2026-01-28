<?php

namespace App\Services\App;

use App\Models\App\AppMemberBase;
use App\Models\App\AppMemberFollow;
use App\Models\App\AppPostBase;

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
}
