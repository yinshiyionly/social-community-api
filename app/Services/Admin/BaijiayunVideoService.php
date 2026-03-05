<?php

namespace App\Services\Admin;

use App\Models\App\AppVideoBaijiayun;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class BaijiayunVideoService
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
        $query = AppVideoBaijiayun::query()
            ->select([
                'id', 'video_id', 'name', 'status', 'publish_status',
                'total_size', 'preface_url', 'play_url',
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

        if (isset($filters['publishStatus']) && $filters['publishStatus'] !== '') {
            $query->where('publish_status', (int) $filters['publishStatus']);
        }

        if (!empty($filters['beginTime'])) {
            $query->where('created_at', '>=', $filters['beginTime']);
        }

        if (!empty($filters['endTime'])) {
            $query->where('created_at', '<=', $filters['endTime']);
        }

        $query->orderByDesc('created_at')->orderByDesc('id');

        return $query->paginate($pageSize, ['*'], 'pageNum', $pageNum);
    }

    /**
     * 获取视频详情
     *
     * @param int $videoId
     * @return AppVideoBaijiayun|null
     */
    public function getDetail(int $videoId): ?AppVideoBaijiayun
    {
        return AppVideoBaijiayun::query()
            ->where('video_id', $videoId)
            ->first();
    }

    /**
     * 检查 videoId 是否存在
     *
     * @param int $videoId
     * @return bool
     */
    public function existsByVideoId(int $videoId): bool
    {
        return AppVideoBaijiayun::query()
            ->where('video_id', $videoId)
            ->exists();
    }

    /**
     * 创建视频
     *
     * @param array $data
     * @return AppVideoBaijiayun
     */
    public function create(array $data): AppVideoBaijiayun
    {
        return AppVideoBaijiayun::query()->create([
            'video_id' => (int) $data['videoId'],
            'name' => $data['name'],
            'status' => isset($data['status']) ? (int) $data['status'] : AppVideoBaijiayun::STATUS_UPLOADING,
            'total_size' => isset($data['totalSize']) ? (string) $data['totalSize'] : '0',
            'preface_url' => $data['prefaceUrl'] ?? null,
            'play_url' => $data['playUrl'] ?? null,
            'length' => isset($data['length']) ? (int) $data['length'] : 0,
            'width' => isset($data['width']) ? (int) $data['width'] : 0,
            'height' => isset($data['height']) ? (int) $data['height'] : 0,
            'publish_status' => isset($data['publishStatus'])
                ? (int) $data['publishStatus']
                : AppVideoBaijiayun::PUBLISH_STATUS_UNPUBLISHED,
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
        $video = AppVideoBaijiayun::query()
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
        if (array_key_exists('totalSize', $data)) {
            $updateData['total_size'] = (string) $data['totalSize'];
        }
        if (array_key_exists('prefaceUrl', $data)) {
            $updateData['preface_url'] = $data['prefaceUrl'];
        }
        if (array_key_exists('playUrl', $data)) {
            $updateData['play_url'] = $data['playUrl'];
        }
        if (array_key_exists('length', $data)) {
            $updateData['length'] = (int) $data['length'];
        }
        if (array_key_exists('width', $data)) {
            $updateData['width'] = (int) $data['width'];
        }
        if (array_key_exists('height', $data)) {
            $updateData['height'] = (int) $data['height'];
        }
        if (array_key_exists('publishStatus', $data)) {
            $updateData['publish_status'] = (int) $data['publishStatus'];
        }

        if (empty($updateData)) {
            return true;
        }

        return $video->update($updateData);
    }

    /**
     * 删除视频（软删除）
     *
     * @param array $videoIds
     * @return int
     */
    public function delete(array $videoIds): int
    {
        return AppVideoBaijiayun::query()
            ->whereIn('video_id', $videoIds)
            ->delete();
    }

    /**
     * 获取被章节使用的视频ID
     *
     * @param array $videoIds
     * @return array
     */
    public function getUsedVideoIds(array $videoIds): array
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

