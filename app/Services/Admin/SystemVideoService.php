<?php

namespace App\Services\Admin;

use App\Models\App\AppVideoSystem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SystemVideoService
{
    /**
     * 获取视频列表（分页）
     *
     * @param array $filters
     * @param int $pageNum
     * @param int $pageSize
     * @return LengthAwarePaginator
     */
    public function getList(array $filters, int $pageNum = 1, int $pageSize = 10): LengthAwarePaginator
    {
        $query = AppVideoSystem::query()
            ->select([
                'video_id', 'name', 'status', 'total_size',
                'preface_url', 'play_url',
                'length', 'width', 'height',
                'created_at', 'updated_at',
            ]);

        if (isset($filters['videoId']) && $filters['videoId'] !== '') {
            $query->where('video_id', (int) $filters['videoId']);
        }

        if (!empty($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', (int) $filters['status']);
        }

        if (!empty($filters['beginTime'])) {
            $query->where('created_at', '>=', $filters['beginTime']);
        }

        if (!empty($filters['endTime'])) {
            $query->where('created_at', '<=', $filters['endTime']);
        }

        $query->orderByDesc('created_at')->orderByDesc('video_id');

        return $query->paginate($pageSize, ['*'], 'pageNum', $pageNum);
    }

    /**
     * 获取视频详情
     *
     * @param int $videoId
     * @return AppVideoSystem|null
     */
    public function getDetail(int $videoId): ?AppVideoSystem
    {
        return AppVideoSystem::query()
            ->where('video_id', $videoId)
            ->first();
    }

    /**
     * 创建视频
     *
     * @param array $data
     * @return AppVideoSystem
     */
    public function create(array $data): AppVideoSystem
    {
        return AppVideoSystem::query()->create([
            'name' => $data['name'],
            'status' => isset($data['status']) ? (int) $data['status'] : AppVideoSystem::STATUS_ENABLED,
            'total_size' => isset($data['totalSize']) ? (string) $data['totalSize'] : '0',
            'preface_url' => $data['prefaceUrl'] ?? null,
            'play_url' => $data['playUrl'] ?? null,
            'length' => isset($data['length']) ? (int) $data['length'] : 0,
            'width' => isset($data['width']) ? (int) $data['width'] : 0,
            'height' => isset($data['height']) ? (int) $data['height'] : 0,
        ]);
    }

    /**
     * 更新视频
     *
     * @param int $videoId
     * @param array $data
     * @return bool
     */
    public function update(int $videoId, array $data): bool
    {
        $video = AppVideoSystem::query()
            ->where('video_id', $videoId)
            ->first();

        if (!$video) {
            return false;
        }

        $updateData = [];

        if (array_key_exists('name', $data)) {
            $updateData['name'] = $data['name'];
        }
        if (array_key_exists('status', $data)) {
            $updateData['status'] = (int) $data['status'];
        }

        if (empty($updateData)) {
            return true;
        }

        return $video->update($updateData);
    }

    /**
     * 删除视频-软删除
     *
     * @param $videoId
     * @return int
     */
    public function delete($videoId): int
    {
        return AppVideoSystem::query()
            ->where('video_id', $videoId)
            ->delete();
    }

    /**
     * 获取被章节使用的视频ID
     *
     * @param $videoId
     * @return array
     */
    public function getUsedVideoId($videoId): array
    {
        if (empty($videoIds)) {
            return [];
        }

        return DB::table('admin_video_chapter_content')
            ->whereIn('video_id', $videoIds)
            ->distinct()
            ->pluck('video_id')
            ->map(function ($videoId) {
                return (int) $videoId;
            })
            ->values()
            ->toArray();
    }
}
