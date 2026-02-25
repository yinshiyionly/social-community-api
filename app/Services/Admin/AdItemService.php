<?php

namespace App\Services\Admin;

use App\Models\App\AppAdItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class AdItemService
{
    /**
     * 获取广告内容列表（分页）
     *
     * @param array $filters 筛选条件
     * @param int $pageNum 页码
     * @param int $pageSize 每页数量
     * @return LengthAwarePaginator
     */
    public function getList(array $filters, int $pageNum = 1, int $pageSize = 10): LengthAwarePaginator
    {
        $query = AppAdItem::query()
            ->select([
                'ad_id', 'space_id', 'ad_title', 'ad_type', 'content_url',
                'target_type', 'target_url', 'sort_num', 'status',
                'start_time', 'end_time', 'created_at',
            ])
            ->with('adSpace:space_id,space_name');

        if (isset($filters['spaceId']) && $filters['spaceId'] !== '') {
            $query->where('space_id', $filters['spaceId']);
        }

        if (!empty($filters['adTitle'])) {
            $query->where('ad_title', 'like', '%' . $filters['adTitle'] . '%');
        }

        if (isset($filters['adType']) && $filters['adType'] !== '') {
            $query->where('ad_type', $filters['adType']);
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['beginTime'])) {
            $query->where('created_at', '>=', $filters['beginTime']);
        }
        if (!empty($filters['endTime'])) {
            $query->where('created_at', '<=', $filters['endTime']);
        }

        $query->orderByDesc('sort_num')->orderByDesc('ad_id');

        return $query->paginate($pageSize, ['*'], 'pageNum', $pageNum);
    }

    /**
     * 获取广告内容详情
     *
     * @param int $adId
     * @return AppAdItem|null
     */
    public function getDetail(int $adId): ?AppAdItem
    {
        return AppAdItem::query()
            ->with('adSpace:space_id,space_name')
            ->where('ad_id', $adId)
            ->first();
    }

    /**
     * 创建广告内容
     *
     * @param array $data
     * @return AppAdItem
     */
    public function create(array $data): AppAdItem
    {
        return AppAdItem::create([
            'space_id' => $data['spaceId'],
            'ad_title' => $data['adTitle'],
            'ad_type' => $data['adType'],
            'content_url' => $data['contentUrl'] ?? '',
            'target_type' => $data['targetType'] ?? AppAdItem::TARGET_TYPE_NONE,
            'target_url' => $data['targetUrl'] ?? '',
            'sort_num' => $data['sortNum'] ?? 0,
            'status' => $data['status'] ?? AppAdItem::STATUS_ONLINE,
            'start_time' => $data['startTime'] ?? null,
            'end_time' => $data['endTime'] ?? null,
            'ext_json' => $data['extJson'] ?? [],
        ]);
    }

    /**
     * 更新广告内容
     *
     * @param int $adId
     * @param array $data
     * @return bool
     */
    public function update(int $adId, array $data): bool
    {
        $adItem = AppAdItem::query()->where('ad_id', $adId)->first();

        if (!$adItem) {
            return false;
        }

        $updateData = [];

        if (isset($data['spaceId'])) {
            $updateData['space_id'] = $data['spaceId'];
        }
        if (isset($data['adTitle'])) {
            $updateData['ad_title'] = $data['adTitle'];
        }
        if (isset($data['adType'])) {
            $updateData['ad_type'] = $data['adType'];
        }
        if (array_key_exists('contentUrl', $data)) {
            $updateData['content_url'] = $data['contentUrl'] ?? '';
        }
        if (array_key_exists('targetType', $data)) {
            $updateData['target_type'] = $data['targetType'] ?? AppAdItem::TARGET_TYPE_NONE;
        }
        if (array_key_exists('targetUrl', $data)) {
            $updateData['target_url'] = $data['targetUrl'] ?? '';
        }
        if (isset($data['sortNum'])) {
            $updateData['sort_num'] = $data['sortNum'];
        }
        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
        }
        if (array_key_exists('startTime', $data)) {
            $updateData['start_time'] = $data['startTime'];
        }
        if (array_key_exists('endTime', $data)) {
            $updateData['end_time'] = $data['endTime'];
        }
        if (array_key_exists('extJson', $data)) {
            $updateData['ext_json'] = $data['extJson'] ?? [];
        }

        return $adItem->update($updateData);
    }

    /**
     * 删除广告内容（支持批量，软删除）
     *
     * @param array $adIds
     * @return int 删除数量
     */
    public function delete(array $adIds): int
    {
        return AppAdItem::query()
            ->whereIn('ad_id', $adIds)
            ->delete();
    }

    /**
     * 修改广告内容状态
     *
     * @param int $adId
     * @param int $status
     * @return bool
     */
    public function changeStatus(int $adId, int $status): bool
    {
        return AppAdItem::query()
                ->where('ad_id', $adId)
                ->update(['status' => $status]) > 0;
    }
}
