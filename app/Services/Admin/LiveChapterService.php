<?php

namespace App\Services\Admin;

use App\Models\App\AppChapterContentLive;
use App\Models\App\AppCourseChapter;
use App\Services\BaijiayunLiveService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class LiveChapterService
{
    /**
     * @var BaijiayunLiveService
     */
    protected $baijiayunService;

    public function __construct(BaijiayunLiveService $baijiayunService)
    {
        $this->baijiayunService = $baijiayunService;
    }

    /**
     * 获取直播课章节列表（分页）
     *
     * @param int $courseId 课程ID
     * @param array $filters 筛选条件
     * @param int $pageNum 页码
     * @param int $pageSize 每页数量
     * @return LengthAwarePaginator
     */
    public function getList(int $courseId, array $filters, int $pageNum = 1, int $pageSize = 10): LengthAwarePaginator
    {
        $query = AppCourseChapter::query()
            ->select([
                'chapter_id', 'course_id', 'chapter_no', 'chapter_title',
                'sort_order', 'status', 'created_at',
            ])
            ->with('liveContent')
            ->where('course_id', $courseId);

        // 按直播状态筛选
        if (isset($filters['liveStatus']) && $filters['liveStatus'] !== '') {
            $liveStatus = $filters['liveStatus'];
            $query->whereHas('liveContent', function ($q) use ($liveStatus) {
                $q->where('live_status', $liveStatus);
            });
        }

        // 按章节标题关键词筛选
        if (!empty($filters['chapterTitle'])) {
            $query->where('chapter_title', 'like', '%' . $filters['chapterTitle'] . '%');
        }

        $query->orderBy('sort_order', 'asc')
            ->orderBy('chapter_id', 'asc');

        return $query->paginate($pageSize, ['*'], 'pageNum', $pageNum);
    }

    /**
     * 获取直播课章节详情
     *
     * @param int $chapterId 章节ID
     * @return AppCourseChapter|null
     */
    public function getDetail(int $chapterId)
    {
        return AppCourseChapter::query()
            ->with('liveContent')
            ->where('chapter_id', $chapterId)
            ->first();
    }

    /**
     * 修改章节状态（上下架）
     *
     * @param int $chapterId 章节ID
     * @param int $status 目标状态
     * @return bool
     */
    public function changeStatus(int $chapterId, int $status): bool
    {
        return AppCourseChapter::query()
                ->where('chapter_id', $chapterId)
                ->update(['status' => $status]) > 0;
    }

    /**
     * 创建直播课章节
     *
     * @param array $data 创建数据
     * @return array ['success' => bool, 'chapter' => ?AppCourseChapter, 'error' => ?string]
     */
    public function create(array $data): array
    {
        // 创建章节记录
        $chapter = AppCourseChapter::create([
            'course_id' => $data['courseId'],
            'chapter_no' => $data['chapterNo'] ?? 0,
            'chapter_title' => $data['chapterTitle'],
            'chapter_subtitle' => $data['chapterSubtitle'] ?? '',
            'cover_image' => $data['coverImage'] ?? '',
            'brief' => $data['brief'] ?? '',
            'sort_order' => $data['sortOrder'] ?? 0,
            'status' => AppCourseChapter::STATUS_ONLINE,
        ]);

        // 调用百家云创建直播间
        $startTime = $data['liveStartTime'];
        $endTime = $data['liveEndTime'];

        Log::info('调用百家云创建直播间', [
            'chapter_id' => $chapter->chapter_id,
            'action' => 'createRoom',
        ]);

        $result = $this->baijiayunService->createRoom(
            $data['chapterTitle'],
            $startTime,
            $endTime
        );

        if (!$result['success']) {
            Log::error('百家云创建直播间失败', [
                'chapter_id' => $chapter->chapter_id,
                'error_code' => $result['error_code'],
                'error_message' => $result['error_message'],
            ]);

            // 删除已创建的章节记录
            $chapter->forceDelete();

            return [
                'success' => false,
                'chapter' => null,
                'error' => '创建直播间失败：' . $result['error_message'],
            ];
        }

        Log::info('百家云创建直播间成功', [
            'chapter_id' => $chapter->chapter_id,
            'room_id' => $result['data']['room_id'] ?? '',
        ]);

        // 创建直播内容记录
        AppChapterContentLive::create([
            'chapter_id' => $chapter->chapter_id,
            'live_platform' => AppChapterContentLive::PLATFORM_BAIJIAYUN,
            'live_room_id' => $result['data']['room_id'] ?? '',
            'live_push_url' => $result['data']['push_url'] ?? '',
            'live_pull_url' => $result['data']['pull_url'] ?? '',
            'live_cover' => $data['liveCover'] ?? '',
            'live_start_time' => $startTime,
            'live_end_time' => $endTime,
            'live_duration' => $data['liveDuration'] ?? 0,
            'live_status' => AppChapterContentLive::LIVE_STATUS_NOT_STARTED,
            'allow_chat' => $data['allowChat'] ?? 1,
            'allow_gift' => $data['allowGift'] ?? 0,
            'attachments' => $data['attachments'] ?? [],
        ]);

        // 重新加载关联
        $chapter->load('liveContent');

        return [
            'success' => true,
            'chapter' => $chapter,
            'error' => null,
        ];
    }

    /**
     * 更新直播课章节
     *
     * @param int $chapterId 章节ID
     * @param array $data 更新数据
     * @return array ['success' => bool, 'error' => ?string]
     */
    public function update(int $chapterId, array $data): array
    {
        $chapter = AppCourseChapter::query()
            ->with('liveContent')
            ->where('chapter_id', $chapterId)
            ->first();

        if (!$chapter) {
            return ['success' => false, 'error' => '直播课章节不存在'];
        }

        $live = $chapter->liveContent;

        // 直播中禁止修改时间字段
        if ($live && $live->isLiving()) {
            if (isset($data['liveStartTime']) || isset($data['liveEndTime'])) {
                return ['success' => false, 'error' => '直播进行中，无法修改时间'];
            }
        }

        // 更新章节字段
        $chapterFields = [
            'chapterTitle'    => 'chapter_title',
            'chapterNo'       => 'chapter_no',
            'chapterSubtitle' => 'chapter_subtitle',
            'coverImage'      => 'cover_image',
            'brief'           => 'brief',
            'sortOrder'       => 'sort_order',
        ];

        $chapterUpdate = [];
        foreach ($chapterFields as $inputKey => $dbKey) {
            if (array_key_exists($inputKey, $data)) {
                $chapterUpdate[$dbKey] = $data[$inputKey];
            }
        }

        if (!empty($chapterUpdate)) {
            $chapter->update($chapterUpdate);
        }

        // 同步更新直播内容字段
        if ($live) {
            $liveFields = [
                'liveCover'     => 'live_cover',
                'allowChat'     => 'allow_chat',
                'allowGift'     => 'allow_gift',
                'attachments'   => 'attachments',
                'liveDuration'  => 'live_duration',
                'liveStartTime' => 'live_start_time',
                'liveEndTime'   => 'live_end_time',
            ];

            $liveUpdate = [];
            foreach ($liveFields as $inputKey => $dbKey) {
                if (array_key_exists($inputKey, $data)) {
                    $liveUpdate[$dbKey] = $data[$inputKey];
                }
            }

            if (!empty($liveUpdate)) {
                $live->update($liveUpdate);
            }
        }

        return ['success' => true, 'error' => null];
    }

    /**
     * 删除直播课章节（支持批量）
     *
     * @param array $chapterIds 章节ID数组
     * @return array ['success' => bool, 'deleted' => int, 'error' => ?string]
     */
    public function delete(array $chapterIds): array
    {
        $chapters = AppCourseChapter::query()
            ->with('liveContent')
            ->whereIn('chapter_id', $chapterIds)
            ->get();

        if ($chapters->isEmpty()) {
            return ['success' => false, 'deleted' => 0, 'error' => '删除失败，直播课章节不存在'];
        }

        // 检查是否有直播中的章节
        foreach ($chapters as $chapter) {
            $live = $chapter->liveContent;
            if ($live && $live->isLiving()) {
                return [
                    'success' => false,
                    'deleted' => 0,
                    'error' => '直播进行中，无法删除',
                ];
            }
        }

        // 对未开始且有直播间的章节关闭百家云直播间
        foreach ($chapters as $chapter) {
            $live = $chapter->liveContent;
            if ($live && $live->isNotStarted() && !empty($live->live_room_id)) {
                Log::info('调用百家云关闭直播间', [
                    'chapter_id' => $chapter->chapter_id,
                    'room_id' => $live->live_room_id,
                    'action' => 'closeRoom',
                ]);

                $result = $this->baijiayunService->closeRoom($live->live_room_id);

                if (!$result['success']) {
                    Log::warning('百家云关闭直播间失败，继续执行本地删除', [
                        'chapter_id' => $chapter->chapter_id,
                        'room_id' => $live->live_room_id,
                        'error_code' => $result['error_code'],
                        'error_message' => $result['error_message'],
                    ]);
                }
            }
        }

        // 软删除关联的直播内容记录
        AppChapterContentLive::query()
            ->whereIn('chapter_id', $chapterIds)
            ->delete();

        // 软删除章节记录
        $deleted = AppCourseChapter::query()
            ->whereIn('chapter_id', $chapterIds)
            ->delete();

        return ['success' => true, 'deleted' => $deleted, 'error' => null];
    }

    /**
     * 同步直播间状态
     *
     * @param int $chapterId 章节ID
     * @return array ['success' => bool, 'data' => ?array, 'error' => ?string]
     */
    public function syncLiveStatus(int $chapterId): array
    {
        $chapter = AppCourseChapter::query()
            ->with('liveContent')
            ->where('chapter_id', $chapterId)
            ->first();

        if (!$chapter) {
            return ['success' => false, 'data' => null, 'error' => '直播课章节不存在或无直播间'];
        }

        $live = $chapter->liveContent;
        if (!$live || empty($live->live_room_id)) {
            return ['success' => false, 'data' => null, 'error' => '直播课章节不存在或无直播间'];
        }

        Log::info('调用百家云查询直播间状态', [
            'chapter_id' => $chapterId,
            'room_id' => $live->live_room_id,
            'action' => 'getRoomInfo',
        ]);

        $result = $this->baijiayunService->getRoomInfo($live->live_room_id);

        if (!$result['success']) {
            Log::error('百家云查询直播间状态失败', [
                'chapter_id' => $chapterId,
                'room_id' => $live->live_room_id,
                'error_code' => $result['error_code'],
                'error_message' => $result['error_message'],
            ]);

            return [
                'success' => false,
                'data' => null,
                'error' => '同步失败：' . $result['error_message'],
            ];
        }

        $roomData = $result['data'];
        $newStatus = isset($roomData['status']) ? (int) $roomData['status'] : $live->live_status;

        $live->update(['live_status' => $newStatus]);

        Log::info('同步直播间状态成功', [
            'chapter_id' => $chapterId,
            'room_id' => $live->live_room_id,
            'old_status' => $live->getOriginal('live_status'),
            'new_status' => $newStatus,
        ]);

        return [
            'success' => true,
            'data' => ['liveStatus' => $newStatus],
            'error' => null,
        ];
    }

    /**
     * 获取直播回放列表
     *
     * @param int $chapterId 章节ID
     * @return array ['success' => bool, 'data' => ?array, 'error' => ?string]
     */
    public function getPlaybackList(int $chapterId): array
    {
        $chapter = AppCourseChapter::query()
            ->with('liveContent')
            ->where('chapter_id', $chapterId)
            ->first();

        if (!$chapter) {
            return ['success' => false, 'data' => null, 'error' => '直播课章节不存在或无直播间'];
        }

        $live = $chapter->liveContent;
        if (!$live || empty($live->live_room_id)) {
            return ['success' => false, 'data' => null, 'error' => '直播课章节不存在或无直播间'];
        }

        Log::info('调用百家云获取回放列表', [
            'chapter_id' => $chapterId,
            'room_id' => $live->live_room_id,
            'action' => 'getPlaybackList',
        ]);

        $result = $this->baijiayunService->getPlaybackList($live->live_room_id);

        if (!$result['success']) {
            Log::error('百家云获取回放列表失败', [
                'chapter_id' => $chapterId,
                'room_id' => $live->live_room_id,
                'error_code' => $result['error_code'],
                'error_message' => $result['error_message'],
            ]);

            return [
                'success' => false,
                'data' => null,
                'error' => '获取回放列表失败：' . $result['error_message'],
            ];
        }

        return [
            'success' => true,
            'data' => $result['data'],
            'error' => null,
        ];
    }

    /**
     * 同步直播回放信息
     *
     * @param int $chapterId 章节ID
     * @return array ['success' => bool, 'data' => ?array, 'error' => ?string]
     */
    public function syncPlayback(int $chapterId): array
    {
        $chapter = AppCourseChapter::query()
            ->with('liveContent')
            ->where('chapter_id', $chapterId)
            ->first();

        if (!$chapter) {
            return ['success' => false, 'data' => null, 'error' => '直播课章节不存在或无直播间'];
        }

        $live = $chapter->liveContent;
        if (!$live || empty($live->live_room_id)) {
            return ['success' => false, 'data' => null, 'error' => '直播课章节不存在或无直播间'];
        }

        Log::info('调用百家云同步回放信息', [
            'chapter_id' => $chapterId,
            'room_id' => $live->live_room_id,
            'action' => 'syncPlayback',
        ]);

        $result = $this->baijiayunService->getPlaybackList($live->live_room_id);

        if (!$result['success']) {
            Log::error('百家云获取回放信息失败', [
                'chapter_id' => $chapterId,
                'room_id' => $live->live_room_id,
                'error_code' => $result['error_code'],
                'error_message' => $result['error_message'],
            ]);

            return [
                'success' => false,
                'data' => null,
                'error' => '同步回放失败：' . $result['error_message'],
            ];
        }

        $playbackList = $result['data'];
        $hasPlayback = is_array($playbackList) && count($playbackList) > 0 ? 1 : 0;
        $playbackUrl = null;
        $playbackDuration = null;

        if ($hasPlayback) {
            $firstPlayback = $playbackList[0];
            $playbackUrl = isset($firstPlayback['playback_url']) ? $firstPlayback['playback_url'] : null;
            $playbackDuration = isset($firstPlayback['duration']) ? (int) $firstPlayback['duration'] : null;
        }

        $live->update([
            'has_playback' => $hasPlayback,
            'playback_url' => $playbackUrl,
            'playback_duration' => $playbackDuration,
        ]);

        Log::info('同步回放信息成功', [
            'chapter_id' => $chapterId,
            'room_id' => $live->live_room_id,
            'has_playback' => $hasPlayback,
            'playback_url' => $playbackUrl,
            'playback_duration' => $playbackDuration,
        ]);

        return [
            'success' => true,
            'data' => [
                'hasPlayback' => $hasPlayback,
                'playbackUrl' => $playbackUrl,
                'playbackDuration' => $playbackDuration,
            ],
            'error' => null,
        ];
    }

}
