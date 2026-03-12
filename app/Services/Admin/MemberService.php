<?php

namespace App\Services\Admin;

use App\Models\App\AppMemberBase;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * 后台会员管理服务。
 *
 * 职责：
 * 1. 提供后台会员列表分页查询；
 * 2. 提供官方会员账号新增与更新能力；
 * 3. 统一封装筛选规则、官方 member_id 分配规则与写库边界。
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
     * 普通注册用户 member_id 起始值（不含）。
     *
     * 说明：
     * - 官方账号占用 [1, OFFICIAL_MEMBER_ID_UPPER_BOUND) 低号段；
     * - 普通注册用户继续使用数据库 sequence，从该值开始递增。
     */
    private const OFFICIAL_MEMBER_ID_UPPER_BOUND = 3545623190;

    /**
     * 官方 member_id 分配锁键。
     *
     * 使用 pg_advisory_xact_lock 确保并发新增官方账号时不会分配到同一 ID。
     */
    private const OFFICIAL_MEMBER_ID_ADVISORY_LOCK_KEY = 2026031201;

    /**
     * 官方会员显式主键写入 SQL。
     *
     * PostgreSQL 的 member_id 列为 GENERATED ALWAYS AS IDENTITY，
     * 必须使用 OVERRIDING SYSTEM VALUE 才能写入自定义主键。
     */
    private const OFFICIAL_MEMBER_INSERT_SQL = <<<SQL
INSERT INTO app_member_base (
    member_id,
    nickname,
    avatar,
    status,
    is_official,
    official_label,
    created_at,
    updated_at
)
OVERRIDING SYSTEM VALUE
VALUES (?, ?, ?, ?, ?, ?, ?, ?)
SQL;

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

    /**
     * 新增官方会员账号。
     *
     * 关键规则：
     * 1. 在事务内获取 advisory lock，串行化官方ID分配；
     * 2. 官方 member_id 仅使用低号段，按“当前最大 + 1”持续递增；
     * 3. 强制写入 is_official=1，避免官方账号语义被调用方误传覆盖。
     *
     * @param array<string, mixed> $data
     * @return AppMemberBase
     */
    public function createOfficial(array $data): AppMemberBase
    {
        return DB::transaction(function () use ($data) {
            $this->lockOfficialMemberIdRange();

            $memberId = $this->generateNextOfficialMemberId();
            $now = now()->format('Y-m-d H:i:s');

            $inserted = DB::insert(self::OFFICIAL_MEMBER_INSERT_SQL, [
                $memberId,
                (string) ($data['nickname'] ?? ''),
                (string) ($data['avatar'] ?? ''),
                (int) ($data['status'] ?? AppMemberBase::STATUS_NORMAL),
                AppMemberBase::OFFICIAL_YES,
                (string) ($data['official_label'] ?? ''),
                $now,
                $now,
            ]);

            if (!$inserted) {
                throw new \RuntimeException('新增官方会员失败');
            }

            $member = AppMemberBase::query()
                ->where('member_id', $memberId)
                ->first();

            if (!$member) {
                throw new \RuntimeException('新增官方会员失败');
            }

            return $member;
        });
    }

    /**
     * 更新官方会员账号（部分更新）。
     *
     * 关键规则：
     * 1. 仅允许更新未软删且 is_official=1 的账号；
     * 2. 仅更新调用方显式传入字段，未传字段保持原值；
     * 3. 不允许通过该方法修改 member_id，避免破坏主键稳定性。
     *
     * @param int $memberId
     * @param array<string, mixed> $data
     * @return bool true=更新成功/幂等成功，false=目标不存在或非官方账号
     */
    public function updateOfficial(int $memberId, array $data): bool
    {
        $member = AppMemberBase::query()
            ->where('member_id', $memberId)
            ->where('is_official', AppMemberBase::OFFICIAL_YES)
            ->first();

        if (!$member) {
            return false;
        }

        $updateData = [];

        if (array_key_exists('nickname', $data)) {
            $updateData['nickname'] = (string) $data['nickname'];
        }
        if (array_key_exists('avatar', $data)) {
            $updateData['avatar'] = (string) $data['avatar'];
        }
        if (array_key_exists('official_label', $data)) {
            $updateData['official_label'] = (string) $data['official_label'];
        }
        if (array_key_exists('status', $data)) {
            $updateData['status'] = (int) $data['status'];
        }

        // 空更新按幂等成功处理，避免前端重复提交触发误报。
        if (empty($updateData)) {
            return true;
        }

        return $member->update($updateData);
    }

    /**
     * 生成下一个官方会员 member_id。
     *
     * 分配规则：
     * 1. 在低号段 [1, OFFICIAL_MEMBER_ID_UPPER_BOUND) 中取当前最大值 + 1；
     * 2. 不补洞，避免软删后复用历史ID导致审计与消息链路歧义；
     * 3. 若触达上界则抛出异常，阻止与普通注册号段冲突。
     *
     * @return int
     */
    public function generateNextOfficialMemberId(): int
    {
        $maxMemberId = DB::table('app_member_base')
            ->where('member_id', '<', self::OFFICIAL_MEMBER_ID_UPPER_BOUND)
            ->max('member_id');

        $nextMemberId = ((int) $maxMemberId) + 1;

        if ($nextMemberId >= self::OFFICIAL_MEMBER_ID_UPPER_BOUND) {
            throw new \RuntimeException('官方会员ID号段已耗尽');
        }

        return $nextMemberId;
    }

    /**
     * 获取官方ID分配事务锁。
     *
     * 说明：
     * - 仅在 PostgreSQL 环境启用 pg_advisory_xact_lock；
     * - 锁生命周期与当前事务一致，事务结束自动释放。
     *
     * @return void
     */
    protected function lockOfficialMemberIdRange(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::select('SELECT pg_advisory_xact_lock(?)', [self::OFFICIAL_MEMBER_ID_ADVISORY_LOCK_KEY]);
    }
}
