<?php

namespace App\Services\Admin;

use App\Models\App\AppCourseBase;
use App\Models\App\AppCourseChapter;
use App\Models\App\AppChapterContentVideo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VideoChapterService
{
    /**
     * 获取章节列表（分页）
     *
     * @param int $courseId
     * @param array $filters
     * @param int $pageNum
     * @param int $pageSize
     * @return LengthAwarePaginator
     */
    public function getList(int $courseId, array $filters, int $pageNum = 1, int $pageSize = 10): LengthAwarePaginator
    {
        $query = AppCourseChapter::query()
            ->with('videoContent')
            ->where('course_id', $courseId);

        // 章节标题搜索
        if (!empty($filters['chapterTitle'])) {
            $query->where('chapter_title', 'like', '%' . $filters['chapterTitle'] . '%');
        }

        // 状态筛选
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        // 是否免费筛选
        if (isset($filters['isFree']) && $filters['isFree'] !== '') {
            $query->where('is_free', $filters['isFree']);
        }

        // 排序
        $query->orderBy('sort_order')->orderBy('chapter_no')->orderBy('chapter_id');

        return $query->paginate($pageSize, ['*'], 'pageNum', $pageNum);
    }

    /**
     * 获取章节列表（不分页，用于排序等）
     *
     * @param int $courseId
     * @return Collection
     */
    public function getAllByCourse(int $courseId): Collection
    {
        return AppCourseChapter::query()
            ->with('videoContent')
            ->where('course_id', $courseId)
            ->orderBy('sort_order')
            ->orderBy('chapter_no')
            ->orderBy('chapter_id')
            ->get();
    }

    /**
     * 获取章节详情
     *
     * @param int $chapterId
     * @return AppCourseChapter|null
     */
    public function getDetail(int $chapterId): ?AppCourseChapter
    {
        return AppCourseChapter::query()
            ->with('videoContent')
            ->where('chapter_id', $chapterId)
            ->first();
    }

    /**
     * 创建章节（含视频内容）
     *
     * @param array $data
     * @return AppCourseChapter
     */
    public function create(array $data): AppCourseChapter
    {
        return DB::transaction(function () use ($data) {
            // 自动计算章节序号
            $chapterNo = $data['chapterNo'] ?? $this->getNextChapterNo($data['courseId']);

            // 创建章节
            $chapter = AppCourseChapter::create([
                'course_id' => $data['courseId'],
                'chapter_no' => $chapterNo,
                'chapter_title' => $data['chapterTitle'],
                'chapter_subtitle' => $data['chapterSubtitle'] ?? null,
                'cover_image' => $data['coverImage'] ?? null,
                'brief' => $data['brief'] ?? null,
                'is_free' => $data['isFree'] ?? 0,
                'is_preview' => $data['isPreview'] ?? 0,
                'unlock_type' => $data['unlockType'] ?? AppCourseChapter::UNLOCK_TYPE_IMMEDIATE,
                'unlock_days' => $data['unlockDays'] ?? 0,
                'unlock_date' => $data['unlockDate'] ?? null,
                'unlock_time' => $data['unlockTime'] ?? null,
                'duration' => $data['duration'] ?? 0,
                'min_learn_time' => $data['minLearnTime'] ?? 0,
                'allow_skip' => $data['allowSkip'] ?? 0,
                'allow_speed' => $data['allowSpeed'] ?? 1,
                'sort_order' => $data['sortOrder'] ?? $chapterNo,
                'status' => AppCourseChapter::STATUS_DRAFT,
            ]);

            // 创建视频内容
            AppChapterContentVideo::create([
                'chapter_id' => $chapter->chapter_id,
                'video_url' => $data['videoUrl'] ?? null,
                'video_id' => $data['videoId'] ?? null,
                'video_source' => $data['videoSource'] ?? AppChapterContentVideo::SOURCE_LOCAL,
                'duration' => $data['duration'] ?? 0,
                'width' => $data['width'] ?? 0,
                'height' => $data['height'] ?? 0,
                'file_size' => $data['fileSize'] ?? 0,
                'cover_image' => $data['videoCoverImage'] ?? null,
                'quality_list' => $data['qualityList'] ?? [],
                'subtitles' => $data['subtitles'] ?? [],
                'attachments' => $data['attachments'] ?? [],
                'allow_download' => $data['allowDownload'] ?? 0,
                'drm_enabled' => $data['drmEnabled'] ?? 0,
            ]);

            // 更新课程章节数
            $this->updateCourseTotalChapter($data['courseId']);

            return $chapter;
        });
    }

    /**
     * 更新章节（含视频内容）
     *
     * @param int $chapterId
     * @param array $data
     * @return bool
     */
    public function update(int $chapterId, array $data): bool
    {
        $chapter = AppCourseChapter::query()->where('chapter_id', $chapterId)->first();

        if (!$chapter) {
            return false;
        }

        return DB::transaction(function () use ($chapter, $data) {
            // 更新章节基础信息
            $chapterData = [];

            if (isset($data['chapterNo'])) {
                $chapterData['chapter_no'] = $data['chapterNo'];
            }
            if (isset($data['chapterTitle'])) {
                $chapterData['chapter_title'] = $data['chapterTitle'];
            }
            if (array_key_exists('chapterSubtitle', $data)) {
                $chapterData['chapter_subtitle'] = $data['chapterSubtitle'];
            }
            if (array_key_exists('coverImage', $data)) {
                $chapterData['cover_image'] = $data['coverImage'];
            }
            if (array_key_exists('brief', $data)) {
                $chapterData['brief'] = $data['brief'];
            }
            if (isset($data['isFree'])) {
                $chapterData['is_free'] = $data['isFree'];
            }
            if (isset($data['isPreview'])) {
                $chapterData['is_preview'] = $data['isPreview'];
            }
            if (isset($data['unlockType'])) {
                $chapterData['unlock_type'] = $data['unlockType'];
            }
            if (isset($data['unlockDays'])) {
                $chapterData['unlock_days'] = $data['unlockDays'];
            }
            if (array_key_exists('unlockDate', $data)) {
                $chapterData['unlock_date'] = $data['unlockDate'];
            }
            if (array_key_exists('unlockTime', $data)) {
                $chapterData['unlock_time'] = $data['unlockTime'];
            }
            if (isset($data['minLearnTime'])) {
                $chapterData['min_learn_time'] = $data['minLearnTime'];
            }
            if (isset($data['allowSkip'])) {
                $chapterData['allow_skip'] = $data['allowSkip'];
            }
            if (isset($data['allowSpeed'])) {
                $chapterData['allow_speed'] = $data['allowSpeed'];
            }
            if (isset($data['sortOrder'])) {
                $chapterData['sort_order'] = $data['sortOrder'];
            }
            if (isset($data['status'])) {
                $chapterData['status'] = $data['status'];
            }
            if (isset($data['duration'])) {
                $chapterData['duration'] = $data['duration'];
            }

            if (!empty($chapterData)) {
                $chapter->update($chapterData);
            }

            // 更新视频内容
            $videoData = [];

            if (array_key_exists('videoUrl', $data)) {
                $videoData['video_url'] = $data['videoUrl'];
            }
            if (array_key_exists('videoId', $data)) {
                $videoData['video_id'] = $data['videoId'];
            }
            if (isset($data['videoSource'])) {
                $videoData['video_source'] = $data['videoSource'];
            }
            if (isset($data['duration'])) {
                $videoData['duration'] = $data['duration'];
            }
            if (isset($data['width'])) {
                $videoData['width'] = $data['width'];
            }
            if (isset($data['height'])) {
                $videoData['height'] = $data['height'];
            }
            if (isset($data['fileSize'])) {
                $videoData['file_size'] = $data['fileSize'];
            }
            if (array_key_exists('videoCoverImage', $data)) {
                $videoData['cover_image'] = $data['videoCoverImage'];
            }
            if (array_key_exists('qualityList', $data)) {
                $videoData['quality_list'] = $data['qualityList'] ?? [];
            }
            if (array_key_exists('subtitles', $data)) {
                $videoData['subtitles'] = $data['subtitles'] ?? [];
            }
            if (array_key_exists('attachments', $data)) {
                $videoData['attachments'] = $data['attachments'] ?? [];
            }
            if (isset($data['allowDownload'])) {
                $videoData['allow_download'] = $data['allowDownload'];
            }
            if (isset($data['drmEnabled'])) {
                $videoData['drm_enabled'] = $data['drmEnabled'];
            }

            if (!empty($videoData)) {
                $videoContent = $chapter->videoContent;
                if ($videoContent) {
                    $videoContent->update($videoData);
                } else {
                    $videoData['chapter_id'] = $chapter->chapter_id;
                    AppChapterContentVideo::create($videoData);
                }
            }

            return true;
        });
    }

    /**
     * 删除章节（支持批量，软删除）
     *
     * @param array $chapterIds
     * @return int
     */
    public function delete(array $chapterIds): int
    {
        $chapters = AppCourseChapter::query()
            ->whereIn('chapter_id', $chapterIds)
            ->whereNull('deleted_at')
            ->get();

        if ($chapters->isEmpty()) {
            return 0;
        }

        $courseId = $chapters->first()->course_id;
        $operatorId = $this->getCurrentOperatorId();

        $count = AppCourseChapter::query()
            ->whereIn('chapter_id', $chapterIds)
            ->whereNull('deleted_at')
            ->update([
                'deleted_at' => now(),
                'deleted_by' => $operatorId,
            ]);

        // 同时软删除视频内容
        AppChapterContentVideo::query()
            ->whereIn('chapter_id', $chapterIds)
            ->whereNull('deleted_at')
            ->update(['deleted_at' => now()]);

        // 更新课程章节数
        if ($count > 0) {
            $this->updateCourseTotalChapter($courseId);
        }

        return $count;
    }

    /**
     * 修改章节状态
     *
     * @param int $chapterId
     * @param int $status
     * @return bool
     */
    public function changeStatus(int $chapterId, int $status): bool
    {
        return AppCourseChapter::query()
                ->where('chapter_id', $chapterId)
                ->update(['status' => $status]) > 0;
    }

    /**
     * 批量更新排序
     *
     * @param array $sortData [['chapterId' => 1, 'sortOrder' => 0], ...]
     * @return bool
     */
    public function batchUpdateSort(array $sortData): bool
    {
        return DB::transaction(function () use ($sortData) {
            foreach ($sortData as $item) {
                AppCourseChapter::query()
                    ->where('chapter_id', $item['chapterId'])
                    ->update(['sort_order' => $item['sortOrder']]);
            }
            return true;
        });
    }

    /**
     * 检查章节是否存在
     *
     * @param int $chapterId
     * @return bool
     */
    public function exists(int $chapterId): bool
    {
        return AppCourseChapter::query()
            ->where('chapter_id', $chapterId)
            ->exists();
    }

    /**
     * 获取下一个章节序号
     *
     * @param int $courseId
     * @return int
     */
    protected function getNextChapterNo(int $courseId): int
    {
        $maxNo = AppCourseChapter::query()
            ->where('course_id', $courseId)
            ->max('chapter_no');

        return ($maxNo ?? 0) + 1;
    }

    /**
     * 更新课程总章节数和总时长
     *
     * @param int $courseId
     */
    protected function updateCourseTotalChapter(int $courseId): void
    {
        $stats = AppCourseChapter::query()
            ->where('course_id', $courseId)
            ->whereNull('deleted_at')
            ->selectRaw('COUNT(*) as total_chapter, COALESCE(SUM(duration), 0) as total_duration')
            ->first();

        AppCourseBase::query()
            ->where('course_id', $courseId)
            ->update([
                'total_chapter' => $stats->total_chapter ?? 0,
                'total_duration' => $stats->total_duration ?? 0,
            ]);
    }

    /**
     * 获取当前操作人ID
     *
     * @return int|null
     */
    protected function getCurrentOperatorId(): ?int
    {
        $request = request();

        if ($request && $request->attributes->has('system_user_id')) {
            return (int)$request->attributes->get('system_user_id');
        }

        if (Auth::guard('admin')->check()) {
            return (int)Auth::guard('admin')->id();
        }

        return null;
    }
}
