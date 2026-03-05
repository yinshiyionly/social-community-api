<?php

namespace App\Services\Admin;

use App\Models\Admin\AdminVideoChapter;
use App\Models\Admin\AdminVideoChapterContent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VideoChapterService
{
    /**
     * 分页列表
     *
     * @param int $courseId
     * @param array $params
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getList(int $courseId, array $params = [])
    {
        $query = AdminVideoChapter::query()
            ->select(['chapter_id', 'course_id', 'chapter_title', 'unlock_time', 'is_free_trial', 'status', 'sort_order', 'created_at'])
            ->byCourse($courseId)
            ->orderBy('sort_order', 'asc')
            ->orderBy('chapter_id', 'asc');

        $pageSize = $params['pageSize'] ?? 10;

        return $query->paginate($pageSize);
    }

    /**
     * 全部列表（用于排序）
     *
     * @param int $courseId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAll(int $courseId)
    {
        return AdminVideoChapter::query()
            ->select(['chapter_id', 'course_id', 'chapter_title', 'sort_order', 'status'])
            ->byCourse($courseId)
            ->orderBy('sort_order', 'asc')
            ->orderBy('chapter_id', 'asc')
            ->get();
    }


    /**
     * 详情
     *
     * @param int $chapterId
     * @return AdminVideoChapter|null
     */
    public function getDetail(int $chapterId)
    {
        return AdminVideoChapter::with('contents:content_id,chapter_id,video_id,sort_order')
            ->find($chapterId);
    }

    /**
     * 创建章节
     *
     * @param array $data
     * @return AdminVideoChapter
     */
    public function store(array $data): AdminVideoChapter
    {
        return DB::transaction(function () use ($data) {
            // 获取当前最大排序值
            $maxSort = AdminVideoChapter::byCourse($data['courseId'])->max('sort_order');

            $chapter = AdminVideoChapter::create([
                'course_id' => $data['courseId'],
                'chapter_title' => $data['chapterTitle'],
                'unlock_time' => $data['unlockTime'] ?? null,
                'is_free_trial' => $data['isFreeTrial'],
                'status' => AdminVideoChapter::STATUS_OFFLINE,
                'sort_order' => ($maxSort ?? 0) + 1,
            ]);

            // 保存关联视频到章节内容表
            if (!empty($data['videoIds'])) {
                $this->syncVideoContents($chapter->chapter_id, $data['videoIds']);
            }

            return $chapter;
        });
    }

    /**
     * 更新章节
     *
     * @param array $data
     * @return AdminVideoChapter
     */
    public function update(array $data): AdminVideoChapter
    {
        return DB::transaction(function () use ($data) {
            $chapter = AdminVideoChapter::findOrFail($data['chapterId']);

            $chapter->update([
                'chapter_title' => $data['chapterTitle'],
                'unlock_time' => $data['unlockTime'] ?? null,
                'is_free_trial' => $data['isFreeTrial'],
            ]);

            // 同步关联视频
            $videoIds = $data['videoIds'] ?? [];
            $this->syncVideoContents($chapter->chapter_id, $videoIds);

            return $chapter;
        });
    }

    /**
     * 同步章节内容（视频关联）
     *
     * @param int $chapterId
     * @param array $videoIds
     */
    private function syncVideoContents(int $chapterId, array $videoIds): void
    {
        // 先删除旧的关联
        AdminVideoChapterContent::where('chapter_id', $chapterId)->delete();

        // 批量插入新的关联
        $contents = [];
        foreach ($videoIds as $index => $videoId) {
            $contents[] = [
                'chapter_id' => $chapterId,
                'video_id' => $videoId,
                'sort_order' => $index + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($contents)) {
            AdminVideoChapterContent::insert($contents);
        }
    }

    /**
     * 更改状态
     *
     * @param int $chapterId
     * @param int $status
     * @return bool
     */
    public function changeStatus(int $chapterId, int $status): bool
    {
        $chapter = AdminVideoChapter::findOrFail($chapterId);
        $chapter->status = $status;
        return $chapter->save();
    }

    /**
     * 批量排序
     *
     * @param array $items [['chapterId' => 1, 'sortOrder' => 1], ...]
     */
    public function batchSort(array $items): void
    {
        DB::transaction(function () use ($items) {
            foreach ($items as $item) {
                AdminVideoChapter::where('chapter_id', $item['chapterId'])
                    ->update(['sort_order' => $item['sortOrder']]);
            }
        });
    }

    /**
     * 删除章节
     *
     * @param array $chapterIds
     */
    public function destroy(array $chapterIds): void
    {
        DB::transaction(function () use ($chapterIds) {
            // 删除章节内容
            AdminVideoChapterContent::whereIn('chapter_id', $chapterIds)->delete();
            // 软删除章节
            AdminVideoChapter::whereIn('chapter_id', $chapterIds)->delete();
        });
    }
}
