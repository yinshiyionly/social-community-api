<?php

namespace App\Services\Admin;

use App\Models\App\AppMemberBase;
use App\Models\App\AppMessageSystem;
use App\Services\App\MessageService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * 后台系统消息服务。
 *
 * 职责：
 * 1. 提供后台系统消息发送能力（广播/定向）；
 * 2. 提供后台系统消息分页查询能力；
 * 3. 提供官方发送者下拉数据，统一约束发送者来源。
 */
class MessageSystemService
{
    /**
     * 发送者下拉字段。
     */
    private const SENDER_OPTION_COLUMNS = [
        'member_id',
        'nickname',
        'avatar',
        'official_label',
    ];

    /**
     * 列表查询字段。
     */
    private const LIST_COLUMNS = [
        'message_id',
        'sender_id',
        'receiver_id',
        'title',
        'content',
        'cover_url',
        'link_type',
        'link_url',
        'is_read',
        'created_at',
        'updated_at',
    ];

    /**
     * 获取官方发送者下拉选项。
     *
     * 查询约束：
     * 1. 仅返回官方账号（is_official=1）；
     * 2. 仅返回正常状态账号（status=1）；
     * 3. 软删账号自动排除（依赖 SoftDeletes 全局作用域）。
     *
     * @return Collection<int, AppMemberBase>
     */
    public function getSenderOptions(): Collection
    {
        return AppMemberBase::query()
            ->select(self::SENDER_OPTION_COLUMNS)
            ->where('is_official', AppMemberBase::OFFICIAL_YES)
            ->where('status', AppMemberBase::STATUS_NORMAL)
            ->orderByDesc('member_id')
            ->get();
    }

    /**
     * 发送系统消息。
     *
     * 发送规则：
     * 1. 未传 member_ids 时按广播发送，receiver_id 写入 NULL；
     * 2. 传入 member_ids 时按定向发送，自动过滤不存在或软删会员；
     * 3. member_ids 全部无效时返回 sentCount=0，且不降级为广播；
     * 4. 定向发送在单事务中写入，确保消息写入与未读数更新一致。
     *
     * @param array<string, mixed> $data
     * @return array{sentCount:int}
     */
    public function send(array $data): array
    {
        $senderId = (int) $data['sender_id'];
        $title = (string) $data['title'];
        $content = (string) $data['content'];
        $options = [
            'cover_url' => $this->normalizeNullableString($data['cover_url'] ?? null),
            'link_type' => $this->normalizeNullableInt($data['link_type'] ?? null),
            'link_url' => $this->normalizeNullableString($data['link_url'] ?? null),
        ];

        // 仅当请求未传 member_ids 时才发送广播，避免“空数组/空值”误触发全员发送。
        if (!array_key_exists('member_ids', $data)) {
            MessageService::createSystemMessage($senderId, null, $title, $content, $options);

            return ['sentCount' => 1];
        }

        $memberIds = is_array($data['member_ids']) ? $data['member_ids'] : [];
        $candidateIds = collect($memberIds)
            ->map(function ($memberId) {
                return (int) $memberId;
            })
            ->filter(function ($memberId) {
                return $memberId > 0;
            })
            ->unique()
            ->values();

        if ($candidateIds->isEmpty()) {
            return ['sentCount' => 0];
        }

        // 无效接收者定义：会员不存在或已软删。禁用账号仍允许接收消息。
        $validMemberIds = AppMemberBase::query()
            ->whereIn('member_id', $candidateIds->all())
            ->pluck('member_id')
            ->map(function ($memberId) {
                return (int) $memberId;
            })
            ->values()
            ->all();

        if (empty($validMemberIds)) {
            return ['sentCount' => 0];
        }

        $sentCount = 0;
        DB::transaction(function () use ($senderId, $title, $content, $options, $validMemberIds, &$sentCount) {
            foreach ($validMemberIds as $receiverId) {
                MessageService::createSystemMessage($senderId, $receiverId, $title, $content, $options);
                $sentCount++;
            }
        });

        return ['sentCount' => $sentCount];
    }

    /**
     * 获取系统消息列表（分页）。
     *
     * 查询规则：
     * 1. 默认按 isBroadcast=1 查询全员广播；
     * 2. isBroadcast、memberId、isRead 严格按字段过滤，不做语义兜底；
     * 3. 时间筛选基于 created_at，支持 beginTime/endTime 闭区间查询；
     * 4. 按 message_id 倒序，保证最新消息优先展示。
     *
     * @param array<string, mixed> $filters
     * @param int $pageNum
     * @param int $pageSize
     * @return LengthAwarePaginator
     */
    public function getList(array $filters, int $pageNum = 1, int $pageSize = 10): LengthAwarePaginator
    {
        $query = AppMessageSystem::query()
            ->select(self::LIST_COLUMNS)
            ->with([
                'sender:member_id,nickname,avatar,official_label',
                'receiver:member_id,nickname,avatar',
            ]);

        $isBroadcast = (!isset($filters['isBroadcast']) || $filters['isBroadcast'] === '')
            ? 1
            : (int) $filters['isBroadcast'];
        if ($isBroadcast === 1) {
            $query->whereNull('receiver_id');
        } else {
            $query->whereNotNull('receiver_id');
        }

        if (isset($filters['memberId']) && $filters['memberId'] !== '') {
            $query->where('receiver_id', (int) $filters['memberId']);
        }

        if (isset($filters['isRead']) && $filters['isRead'] !== '') {
            $query->where('is_read', (int) $filters['isRead']);
        }

        if (!empty($filters['beginTime'])) {
            $query->where('created_at', '>=', $filters['beginTime']);
        }

        if (!empty($filters['endTime'])) {
            $query->where('created_at', '<=', $filters['endTime']);
        }

        $query->orderByDesc('message_id');

        return $query->paginate($pageSize, ['*'], 'pageNum', $pageNum);
    }

    /**
     * 规范化可空字符串。
     *
     * @param mixed $value
     * @return string|null
     */
    protected function normalizeNullableString($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $stringValue = trim((string) $value);

        return $stringValue === '' ? null : $stringValue;
    }

    /**
     * 规范化可空整数。
     *
     * @param mixed $value
     * @return int|null
     */
    protected function normalizeNullableInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
