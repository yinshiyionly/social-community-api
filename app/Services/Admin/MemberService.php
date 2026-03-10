<?php

namespace App\Services\Admin;

use App\Models\App\AppMemberBase;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * 后台会员查询服务。
 *
 * 职责：
 * 1. 提供后台会员列表分页查询；
 * 2. 统一封装筛选规则与排序规则，避免控制器重复拼接条件。
 */
class MemberService
{
    /**
     * 列表查询字段。
     */
    private const LIST_COLUMNS = [
        'member_id',
        'phone',
        'nickname',
        'avatar',
        'gender',
        'status',
        'is_official',
        'official_label',
        'fans_count',
        'following_count',
        'like_count',
        'creation_count',
        'favorite_count',
        'created_at',
    ];

    /**
     * 获取会员列表（分页）。
     *
     * 关键规则：
     * 1. 默认仅查询未软删会员（依赖模型 SoftDeletes 全局作用域）；
     * 2. memberId/phone 为精确筛选，nickname 为模糊筛选；
     * 3. 后台统一按 member_id 倒序，保证最新注册用户优先展示。
     *
     * @param array<string, mixed> $filters
     * @param int $pageNum
     * @param int $pageSize
     * @return LengthAwarePaginator
     */
    public function getList(array $filters, int $pageNum = 1, int $pageSize = 10): LengthAwarePaginator
    {
        $query = AppMemberBase::query()
            ->select(self::LIST_COLUMNS);

        if (!empty($filters['memberId'])) {
            $query->where('member_id', (int) $filters['memberId']);
        }

        if (!empty($filters['phone'])) {
            $query->where('phone', (string) $filters['phone']);
        }

        if (!empty($filters['nickname'])) {
            $query->where('nickname', 'like', '%' . $filters['nickname'] . '%');
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', (int) $filters['status']);
        }

        if (isset($filters['isOfficial']) && $filters['isOfficial'] !== '') {
            $query->where('is_official', (int) $filters['isOfficial']);
        }

        if (!empty($filters['beginTime'])) {
            $query->where('created_at', '>=', $filters['beginTime']);
        }

        if (!empty($filters['endTime'])) {
            $query->where('created_at', '<=', $filters['endTime']);
        }

        $query->orderByDesc('member_id');

        return $query->paginate($pageSize, ['*'], 'pageNum', $pageNum);
    }
}

