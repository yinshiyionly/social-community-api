<?php

namespace App\Services\Admin;

use App\Models\App\AppChapterContentLive;
use App\Models\App\AppCourseBase;
use App\Models\App\AppCourseChapter;
use App\Models\App\AppLiveRoom;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 后台直播课章节服务。
 *
 * 职责：
 * 1. 提供直播章节分页查询与详情查询；
 * 2. 在创建/更新章节时联动写入章节基础表与直播内容表；
 * 3. 根据 liveRoomId 回填直播间最小元数据，保证章节与直播间数据一致。
 */
class LiveChapterService
{
    /**
     * 获取直播课章节分页列表。
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
            ->with('liveContent:id,chapter_id,live_room_id,live_status')
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
            ->whereHas('course', function ($query) {
                $query->where('play_type', AppCourseBase::PLAY_TYPE_LIVE)
                    ->whereNull('deleted_at');
            })
            ->orderBy('sort_order', 'asc')
            ->orderBy('chapter_id', 'asc')
            ->paginate($pageSize, ['*'], 'pageNum', $pageNum);
    }

    /**
     * 获取直播章节全部列表（用于直播课课程表）。
     *
     * 关键规则：
     * 1. 返回章节基础字段 + 直播内容字段，字段口径与课程表文档保持一致；
     * 2. 仅返回直播课（play_type=1）课程章节；
     * 3. 按 sort_order、chapter_id 升序输出，保证课表展示稳定。
     *
     * @param int $courseId 课程ID
     * @return Collection<int, AppCourseChapter>
     */
    public function getAll(int $courseId): Collection
    {
        return AppCourseChapter::query()
            ->with([
                'liveContent:id,chapter_id,live_room_id,live_platform,live_cover,live_start_time,live_end_time,live_duration,live_status,has_playback,playback_url,allow_chat,online_count,max_online_count',
            ])
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
            ->whereHas('course', function ($query) {
                $query->where('play_type', AppCourseBase::PLAY_TYPE_LIVE)
                    ->whereNull('deleted_at');
            })
            ->orderBy('sort_order', 'asc')
            ->orderBy('chapter_id', 'asc')
            ->get();
    }

    /**
     * 获取直播课章节详情。
     *
     * @param int $chapterId 章节ID
     * @return AppCourseChapter|null
     */
    public function getDetail(int $chapterId): ?AppCourseChapter
    {
        return AppCourseChapter::query()
            ->with('liveContent:id,chapter_id,live_room_id,live_platform,live_status')
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
            ->where('chapter_id', $chapterId)
            ->whereHas('course', function ($query) {
                $query->where('play_type', AppCourseBase::PLAY_TYPE_LIVE)
                    ->whereNull('deleted_at');
            })
            ->first();
    }

    /**
     * 创建直播章节。
     *
     * 关键规则：
     * 1. 创建时将章节追加到课程尾部（chapter_no/sort_order 递增）；
     * 2. 章节基础字段落库到 app_course_chapter；
     * 3. 直播内容按 liveRoomId 回填最小元数据到 app_chapter_content_live。
     *
     * @param array<string, mixed> $data 已通过校验的入参
     * @return AppCourseChapter
     */
    public function store(array $data): AppCourseChapter
    {
        return DB::transaction(function () use ($data) {
            $courseId = (int)$data['courseId'];
            $room = $this->getLiveRoom((int)$data['liveRoomId']);

            // $maxSortOrder = AppCourseChapter::query()->byCourse($courseId)->max('sort_order');
            // $maxChapterNo = AppCourseChapter::query()->byCourse($courseId)->max('chapter_no');

            $chapter = AppCourseChapter::query()->create([
                'course_id' => $courseId,
                'chapter_no' => 0,
                'chapter_title' => $data['chapterTitle'],
                'chapter_subtitle' => $data['chapterSubtitle'],
                'cover_image' => $data['coverImage'],
                'is_free' => $data['isFree'],
                'unlock_type' => (int)$data['unlockType'],
                'unlock_days' => $this->resolveUnlockDays($data),
                'unlock_date' => $this->resolveUnlockDate($data),
                'chapter_start_time' => $data['chapterStartTime'],
                'chapter_end_time' => $data['chapterEndTime'],
                'sort_order' => 0,
                'status' => (int)$data['status'],
            ]);

            $this->syncLiveContent($chapter->chapter_id, $room, $data);

            Log::info('直播课章节创建成功', [
                'course_id' => $courseId,
                'chapter_id' => $chapter->chapter_id,
                'live_room_id' => (int)$room->room_id,
            ]);

            return $chapter->fresh('liveContent');
        });
    }

    /**
     * 更新直播章节。
     *
     * 关键规则：
     * 1. status 在更新接口内直接落库，不再依赖单独改状态接口；
     * 2. 每次更新都会按 liveRoomId 回填最小直播元数据；
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
            $room = $this->getLiveRoom((int)$data['liveRoomId']);

            $chapter = AppCourseChapter::query()
                ->where('chapter_id', $chapterId)
                ->where('course_id', $courseId)
                ->firstOrFail();

            $chapter->update([
                'chapter_title' => $data['chapterTitle'],
                'chapter_subtitle' => $data['chapterSubtitle'],
                'cover_image' => $data['coverImage'],
                'is_free' => $data['isFree'],
                'unlock_type' => (int)$data['unlockType'],
                'unlock_days' => $this->resolveUnlockDays($data),
                'unlock_date' => $this->resolveUnlockDate($data),
                'chapter_start_time' => $data['chapterStartTime'],
                'chapter_end_time' => $data['chapterEndTime'],
                'status' => (int)$data['status'],
            ]);

            $this->syncLiveContent($chapter->chapter_id, $room, $data);

            Log::info('直播课章节更新成功', [
                'chapter_id' => $chapterId,
                'live_room_id' => (int)$room->room_id,
            ]);

            return $chapter->fresh('liveContent');
        });
    }

    /**
     * 删除直播章节（单个）。
     *
     * 失败策略：
     * - 任一步骤异常均回滚事务，避免章节基础表与内容表数据不一致。
     *
     * @param int $chapterId 章节ID
     * @return void
     */
    public function destroy(int $chapterId): void
    {
        DB::transaction(function () use ($chapterId) {
            $chapter = AppCourseChapter::query()->findOrFail($chapterId);

            // 内容表对 chapter_id 有唯一索引，删除章节时物理删除历史内容，避免后续唯一键占用。
            AppChapterContentLive::withTrashed()
                ->where('chapter_id', $chapterId)
                ->forceDelete();

            $chapter->delete();

            Log::info('直播课章节删除成功', [
                'chapter_id' => $chapterId,
            ]);
        });
    }

    /**
     * 同步直播章节内容（最小元数据快照）。
     *
     * 回填字段：
     * - live_room_id/live_platform/live_push_url/live_pull_url/live_cover；
     * - live_start_time/live_end_time 使用章节时间，保证课表展示一致。
     *
     * @param int $chapterId 章节ID
     * @param AppLiveRoom $room 直播间
     * @param array<string, mixed> $data 已通过校验的入参
     * @return void
     */
    private function syncLiveContent(int $chapterId, AppLiveRoom $room, array $data): void
    {
        $payload = [
            'live_room_id' => (string)$room->room_id,
            'live_platform' => (string)$room->live_platform,
            'live_push_url' => $room->push_url,
            'live_pull_url' => $room->pull_url,
            // 直播间封面优先使用原始存储值，避免把临时签名 URL 落库。
            'live_cover' => $room->getRawOriginal('room_cover') ?: $room->room_cover,
            'live_start_time' => $data['chapterStartTime'],
            'live_end_time' => $data['chapterEndTime'],
        ];

        $content = AppChapterContentLive::withTrashed()
            ->where('chapter_id', $chapterId)
            ->first();

        if ($content) {
            // 先恢复软删记录再更新，避免唯一索引 chapter_id 冲突。
            if ($content->trashed()) {
                $content->restore();
            }

            $content->fill($payload);
            $content->save();
            return;
        }

        AppChapterContentLive::query()->create(array_merge($payload, [
            'chapter_id' => $chapterId,
        ]));
    }

    /**
     * 查询可用直播间。
     *
     * @param int $liveRoomId 直播间ID
     * @return AppLiveRoom
     */
    private function getLiveRoom(int $liveRoomId): AppLiveRoom
    {
        return AppLiveRoom::query()
            ->select([
                'room_id',
                'live_platform',
                'push_url',
                'pull_url',
                'room_cover',
            ])
            ->where('room_id', $liveRoomId)
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
