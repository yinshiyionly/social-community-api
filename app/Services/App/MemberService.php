<?php

namespace App\Services\App;

use App\Constant\AppResponseCode;
use App\Models\App\AppMemberBase;
use App\Models\App\AppMemberFollow;
use App\Models\App\AppMemberOauth;
use App\Models\App\AppPostBase;
use App\Models\App\AppPostCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * App 端会员服务。
 *
 * 职责：
 * 1. 提供会员主页、关注关系、个人资料等查询与更新能力；
 * 2. 封装会员账号相关业务规则，避免控制器直接拼装复杂查询；
 * 3. 处理会员注销等存在事务边界与副作用的写入操作。
 */
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
        'favorite_count',
        'like_count',
        'points'
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
        'created_at',
    ];

    /**
     * 统计关联
     */
    private const POST_STAT_RELATION = 'stat:post_id,like_count,view_count';

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
            ->with([
                self::POST_STAT_RELATION,
                'member:member_id,nickname,avatar',
            ])
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
            ->select(['collection_id', 'member_id', 'post_id', 'created_at'])
            ->with([
                'post' => function ($query) {
                    $query->select(self::POST_LIST_COLUMNS)
                        ->with([
                            self::POST_STAT_RELATION,
                            'member:member_id,nickname,avatar',
                        ])
                        ->approved()
                        ->visible();
                },
            ])
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

    /**
     * 个人信息查询字段
     */
    private const INFO_COLUMNS = [
        'member_id',
        'phone',
        'nickname',
        'avatar',
        'gender',
        'birthday',
        'bio',
        'level',
        'points',
        'coin',
        'fans_count',
        'following_count',
        'like_count',
        'creation_count',
        'favorite_count',
    ];

    /**
     * 获取当前登录用户个人信息
     *
     * @param int $memberId 用户ID
     * @return AppMemberBase|null
     */
    public function getMemberInfo(int $memberId)
    {
        return AppMemberBase::query()
            ->select(self::INFO_COLUMNS)
            ->where('member_id', $memberId)
            ->first();
    }

    /**
     * 更新用户个人信息
     *
     * @param int $memberId 用户ID
     * @param array $data 更新数据
     * @return bool
     */
    public function updateMemberInfo(int $memberId, array $data): bool
    {
        // 过滤允许更新的字段
        $allowedFields = ['nickname', 'avatar', 'gender', 'birthday', 'bio'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (empty($updateData)) {
            return true;
        }

        return AppMemberBase::query()
                ->where('member_id', $memberId)
                ->update($updateData) > 0;
    }

    /**
     * 注销账号（软删 + 禁用 + 清理 OAuth 绑定）。
     *
     * 关键规则：
     * 1. 使用事务 + 行锁保证同一账号并发注销时结果一致；
     * 2. 官方账号（is_official=1）禁止注销，避免影响系统官方能力；
     * 3. 账号注销后清理 app_member_oauth 记录，防止旧绑定继续参与登录流程。
     *
     * 失败策略：
     * - 可识别业务失败返回 code/message，由控制器映射响应；
     * - 不可预期异常记录错误日志并返回 500 通用失败。
     *
     * @param int $memberId
     * @param string $reason
     * @param string|null $clientIp
     * @param string|null $userAgent
     * @return array{success:bool, code?:int, message?:string}
     */
    public function cancelAccount(
        int $memberId,
        string $reason = '',
        ?string $clientIp = null,
        ?string $userAgent = null
    ): array
    {
        try {
            return DB::transaction(function () use ($memberId, $reason, $clientIp, $userAgent) {
                $member = AppMemberBase::query()
                    ->where('member_id', $memberId)
                    ->lockForUpdate()
                    ->first();

                if (!$member) {
                    return [
                        'success' => false,
                        'code' => AppResponseCode::DATA_NOT_FOUND,
                        'message' => '用户不存在',
                    ];
                }

                // 官方账号承担系统消息等能力，不允许走自助注销流程。
                if ((int)$member->is_official === AppMemberBase::OFFICIAL_YES) {
                    return [
                        'success' => false,
                        'code' => AppResponseCode::FORBIDDEN,
                        'message' => '当前账号不允许注销',
                    ];
                }

                $member->status = AppMemberBase::STATUS_DISABLED;
                if (!$member->save()) {
                    return [
                        'success' => false,
                        'code' => AppResponseCode::SERVER_ERROR,
                        'message' => '注销失败，请稍后重试',
                    ];
                }

                if (!$member->delete()) {
                    return [
                        'success' => false,
                        'code' => AppResponseCode::SERVER_ERROR,
                        'message' => '注销失败，请稍后重试',
                    ];
                }

                $deletedOauthCount = AppMemberOauth::query()
                    ->where('member_id', $memberId)
                    ->delete();

                Log::info('App会员账号注销成功', [
                    'member_id' => $memberId,
                    'reason' => $reason,
                    'ip' => $clientIp,
                    'user_agent' => $userAgent,
                    'deleted_oauth_count' => $deletedOauthCount,
                ]);

                return ['success' => true];
            });
        } catch (\Throwable $e) {
            Log::error('App会员账号注销失败', [
                'member_id' => $memberId,
                'reason' => $reason,
                'ip' => $clientIp,
                'user_agent' => $userAgent,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'code' => AppResponseCode::SERVER_ERROR,
                'message' => '注销失败，请稍后重试',
            ];
        }
    }
}
