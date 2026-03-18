<?php

namespace App\Services\Admin;

use App\Models\App\AppChapterContentVideo;
use App\Models\App\AppCourseChapter;
use App\Models\App\AppVideoSystem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * 后台录播课章节服务。
 *
 * 职责：
 * 1. 提供录播章节分页查询、详情查询与排序能力；
 * 2. 在创建/更新章节时联动写入章节基础表与视频内容表；
 * 3. 基于系统视频库 `videoId` 回填章节视频元数据，保证数据一致性。
 */
class VideoChapterService
{
    /**
     * 获取录播章节分页列表。
     *
     * @param int $courseId 课程ID
     * @param array<string, mixed> $params 分页参数
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getList(int $courseId, array $params = [])
    {
        $pageNum = (int)($params['pageNum'] ?? 1);
        $pageSize = (int)($params['pageSize'] ?? 10);

        return AppCourseChapter::query()
            ->with('videoContent:id,chapter_id,video_id')
            ->select([
                'chapter_id',
                'course_id',
                'chapter_title',
                'chapter_subtitle',
                'cover_image',
                'is_free',
                'unlock_type',
                'unlock_days',
                'unlock_date',
                'chapter_start_time',
                'chapter_end_time',
                'status',
                'sort_order',
                'created_at',
            ])
            ->byCourse($courseId)
            ->orderBy('sort_order', 'asc')
            ->orderBy('chapter_id', 'asc')
            ->paginate($pageSize, ['*'], 'pageNum', $pageNum);
    }

    /**
     * 获取录播章节全部列表（用于排序）。
     *
     * @param int $courseId 课程ID
     * @return Collection<int, AppCourseChapter>
     */
    public function getAll(int $courseId): Collection
    {
        return AppCourseChapter::query()
            ->with('videoContent:id,chapter_id,video_id')
            ->select([
                'chapter_id',
                'course_id',
                'chapter_title',
                'chapter_subtitle',
                'cover_image',
                'is_free',
                'unlock_type',
                'unlock_days',
                'unlock_date',
                'chapter_start_time',
                'chapter_end_time',
                'status',
                'sort_order',
                'created_at',
            ])
            ->byCourse($courseId)
            ->orderBy('sort_order', 'asc')
            ->orderBy('chapter_id', 'asc')
            ->get();
    }

    /**
     * 获取录播章节详情。
     *
     * @param int $chapterId 章节ID
     * @return AppCourseChapter|null
     */
    public function getDetail(int $chapterId): ?AppCourseChapter
    {
        return AppCourseChapter::query()
            ->with('videoContent:id,chapter_id,video_id')
            ->select([
                'chapter_id',
                'course_id',
                'chapter_title',
                'chapter_subtitle',
                'cover_image',
                'is_free',
                'unlock_type',
                'unlock_days',
                'unlock_date',
                'chapter_start_time',
                'chapter_end_time',
                'status',
                'sort_order',
                'created_at',
                'updated_at',
            ])
            ->find($chapterId);
    }

    /**
     * 创建录播章节。
     *
     * 关键规则：
     * 1. 每个章节只关联一个系统视频；
     * 2. 章节时长 `duration` 以系统视频 `length` 为准；
     * 3. `unlock_days/unlock_date` 按 `unlockType` 做互斥落库。
     *
     * @param array<string, mixed> $data 已通过校验的入参
     * @return AppCourseChapter
     */
    public function store(array $data): AppCourseChapter
    {
        return DB::transaction(function () use ($data) {
            $courseId = (int)$data['courseId'];
            $video = $this->getSystemVideo((int)$data['videoId']);

            $maxSortOrder = AppCourseChapter::query()->byCourse($courseId)->max('sort_order');
            $maxChapterNo = AppCourseChapter::query()->byCourse($courseId)->max('chapter_no');

            $chapter = AppCourseChapter::query()->create([
                'course_id' => $courseId,
                'chapter_no' => ($maxChapterNo ?? 0) + 1,
                'chapter_title' => $data['chapterTitle'],
                'chapter_subtitle' => $data['chapterSubtitle'],
                'cover_image' => $data['coverImage'],
                'is_free' => (int)$data['isFree'],
                'unlock_type' => (int)$data['unlockType'],
                'unlock_days' => $this->resolveUnlockDays($data),
                'unlock_date' => $this->resolveUnlockDate($data),
                'chapter_start_time' => $data['chapterStartTime'],
                'chapter_end_time' => $data['chapterEndTime'],
                'duration' => (int)$video->length,
                'status' => (int)$data['status'],
                'sort_order' => ($maxSortOrder ?? 0) + 1,
            ]);

            $this->syncVideoContent($chapter->chapter_id, $video);

            return $chapter->fresh('videoContent');
        });
    }

    /**
     * 更新录播章节。
     *
     * 关键规则：
     * 1. 状态更新合并在本接口中，不再单独提供改状态入口；
     * 2. 每次更新都按最新 `videoId` 刷新章节视频元数据；
     * 3. 章节仅允许在所属课程内更新，避免跨课程误写。
     *
     * @param array<string, mixed> $data 已通过校验的入参
     * @return AppCourseChapter
     */
    public function update(array $data): AppCourseChapter
    {
        return DB::transaction(function () use ($data) {
            $courseId = (int)$data['courseId'];
            $chapterId = (int)$data['chapterId'];
            $video = $this->getSystemVideo((int)$data['videoId']);

            $chapter = AppCourseChapter::query()
                ->where('chapter_id', $chapterId)
                ->where('course_id', $courseId)
                ->firstOrFail();

            $chapter->update([
                'chapter_title' => $data['chapterTitle'],
                'chapter_subtitle' => $data['chapterSubtitle'],
                'cover_image' => $data['coverImage'],
                'is_free' => (int)$data['isFree'],
                'unlock_type' => (int)$data['unlockType'],
                'unlock_days' => $this->resolveUnlockDays($data),
                'unlock_date' => $this->resolveUnlockDate($data),
                'chapter_start_time' => $data['chapterStartTime'],
                'chapter_end_time' => $data['chapterEndTime'],
                'duration' => (int)$video->length,
                'status' => (int)$data['status'],
            ]);

            $this->syncVideoContent($chapter->chapter_id, $video);

            return $chapter->fresh('videoContent');
        });
    }

    /**
     * 批量更新章节排序。
     *
     * @param array<int, array{chapterId:int, sortOrder:int}> $items
     * @return void
     */
    public function batchSort(array $items): void
    {
        DB::transaction(function () use ($items) {
            foreach ($items as $item) {
                AppCourseChapter::query()
                    ->where('chapter_id', $item['chapterId'])
                    ->update(['sort_order' => $item['sortOrder']]);
            }
        });
    }

    /**
     * 删除录播章节（单个）。
     *
     * 失败策略：
     * - 任一删除步骤抛异常时回滚事务，避免章节与内容数据不一致。
     *
     * @param int $chapterId 章节ID
     * @return void
     */
    public function destroy(int $chapterId): void
    {
        DB::transaction(function () use ($chapterId) {
            $chapter = AppCourseChapter::query()->findOrFail($chapterId);

            // 章节内容表带唯一索引 chapter_id，删除章节时直接物理删除内容，避免历史软删记录占用唯一键。
            AppChapterContentVideo::withTrashed()
                ->where('chapter_id', $chapterId)
                ->forceDelete();

            $chapter->delete();
        });
    }

    /**
     * 同步章节视频内容。
     *
     * @param int $chapterId 章节ID
     * @param AppVideoSystem $video 系统视频
     * @return void
     */
    private function syncVideoContent(int $chapterId, AppVideoSystem $video): void
    {
        $payload = [
            'video_url' => $video->getRawOriginal('play_url') ?: $video->play_url,
            'video_id' => (string)$video->video_id,
            'video_source' => AppChapterContentVideo::SOURCE_LOCAL,
            'duration' => (int)$video->length,
            'width' => (int)$video->width,
            'height' => (int)$video->height,
            'file_size' => (int)$video->total_size,
            'cover_image' => $video->getRawOriginal('preface_url') ?: $video->preface_url,
        ];

        $content = AppChapterContentVideo::withTrashed()
            ->where('chapter_id', $chapterId)
            ->first();

        if ($content) {
            // 先恢复软删记录，再更新元数据，避免唯一索引 chapter_id 冲突。
            if ($content->trashed()) {
                $content->restore();
            }

            $content->fill($payload);
            $content->save();
            return;
        }

        AppChapterContentVideo::query()->create(array_merge($payload, [
            'chapter_id' => $chapterId,
        ]));
    }

    /**
     * 获取可用的系统视频。
     *
     * @param int $videoId 系统视频ID
     * @return AppVideoSystem
     */
    private function getSystemVideo(int $videoId): AppVideoSystem
    {
        return AppVideoSystem::query()
            ->select([
                'video_id',
                'total_size',
                'preface_url',
                'play_url',
                'length',
                'width',
                'height',
            ])
            ->where('video_id', $videoId)
            ->whereNull('deleted_at')
            ->firstOrFail();
    }

    /**
     * 计算落库 unlock_days。
     *
     * @param array<string, mixed> $data
     * @return int
     */
    private function resolveUnlockDays(array $data): int
    {
        if ((int)$data['unlockType'] === AppCourseChapter::UNLOCK_TYPE_DAYS) {
            return (int)$data['unlockDays'];
        }

        return 0;
    }

    /**
     * 计算落库 unlock_date。
     *
     * @param array<string, mixed> $data
     * @return string|null
     */
    private function resolveUnlockDate(array $data): ?string
    {
        if ((int)$data['unlockType'] === AppCourseChapter::UNLOCK_TYPE_DATE) {
            return (string)$data['unlockDate'];
        }

        return null;
    }
}
