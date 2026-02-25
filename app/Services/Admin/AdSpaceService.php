<?php

namespace App\Services\Admin;

use App\Models\App\AppAdSpace;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class AdSpaceService
{
    /**
     * 获取广告位列表（分页）
     *
     * @param array $filters 筛选条件
     * @param int $pageNum 页码
     * @param int $pageSize 每页数量
     * @return LengthAwarePaginator
     */
    public function getList(array $filters, int $pageNum = 1, int $pageSize = 10): LengthAwarePaginator
    {
        $query = AppAdSpace::query()
            ->select(['space_id', 'space_name', 'space_code', 'platform', 'width', 'height', 'max_ads', 'status', 'created_at']);

        if (!empty($filters['spaceName'])) {
            $query->where('space_name', 'like', '%' . $filters['spaceName'] . '%');
        }

        if (!empty($filters['spaceCode'])) {
            $query->where('space_code', 'like', '%' . $filters['spaceCode'] . '%');
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['platform']) && $filters['platform'] !== '') {
            $query->where('platform', $filters['platform']);
        }

        $query->orderByDesc('space_id');

        return $query->paginate($pageSize, ['*'], 'pageNum', $pageNum);
    }

    /**
     * 获取广告位详情
     *
     * @param int $spaceId
     * @return AppAdSpace|null
     */
    public function getDetail(int $spaceId): ?AppAdSpace
    {
        return AppAdSpace::query()
            ->where('space_id', $spaceId)
            ->first();
    }

    /**
     * 创建广告位
     *
     * @param array $data
     * @return AppAdSpace
     */
    public function create(array $data): AppAdSpace
    {
        return AppAdSpace::create([
            'space_name' => $data['spaceName'],
            'space_code' => $data['spaceCode'],
            'platform' => $data['platform'] ?? AppAdSpace::PLATFORM_ALL,
            'width' => $data['width'] ?? 0,
            'height' => $data['height'] ?? 0,
            'max_ads' => $data['maxAds'] ?? 0,
            'status' => $data['status'] ?? AppAdSpace::STATUS_ENABLED,
        ]);
    }

    /**
     * 更新广告位
     *
     * @param int $spaceId
     * @param array $data
     * @return bool
     */
    public function update(int $spaceId, array $data): bool
    {
        $space = AppAdSpace::query()->where('space_id', $spaceId)->first();

        if (!$space) {
            return false;
        }

        $updateData = [];

        if (isset($data['spaceName'])) {
            $updateData['space_name'] = $data['spaceName'];
        }
        if (isset($data['spaceCode'])) {
            $updateData['space_code'] = $data['spaceCode'];
        }
        if (isset($data['platform'])) {
            $updateData['platform'] = $data['platform'];
        }
        if (isset($data['width'])) {
            $updateData['width'] = $data['width'];
        }
        if (isset($data['height'])) {
            $updateData['height'] = $data['height'];
        }
        if (isset($data['maxAds'])) {
            $updateData['max_ads'] = $data['maxAds'];
        }
        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
        }

        return $space->update($updateData);
    }

    /**
     * 删除广告位（支持批量，软删除）
     *
     * @param array $spaceIds
     * @return int 删除数量
     */
    public function delete(array $spaceIds): int
    {
        return AppAdSpace::query()
            ->whereIn('space_id', $spaceIds)
            ->delete();
    }

    /**
     * 修改广告位状态
     *
     * @param int $spaceId
     * @param int $status
     * @return bool
     */
    public function changeStatus(int $spaceId, int $status): bool
    {
        return AppAdSpace::query()
                ->where('space_id', $spaceId)
                ->update(['status' => $status]) > 0;
    }

    /**
     * 检查广告位下是否有广告内容
     *
     * @param int $spaceId
     * @return bool
     */
    public function hasAdItems(int $spaceId): bool
    {
        return AppAdSpace::query()
            ->where('space_id', $spaceId)
            ->whereHas('adItems')
            ->exists();
    }

    /**
     * 获取下拉选项列表（只返回启用状态）
     *
     * @return Collection
     */
    public function getOptions(): Collection
    {
        return AppAdSpace::query()
            ->select(['space_id', 'space_name', 'space_code'])
            ->enabled()
            ->orderByDesc('space_id')
            ->get();
    }
}
