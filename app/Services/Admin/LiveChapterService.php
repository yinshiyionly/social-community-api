<?php

namespace App\Services\Admin;

use App\Models\App\AppCourseChapter;
use App\Models\App\AppChapterContentLive;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LiveChapterService
{
    /**
     * 获取直播课章节分页列表
     *
     * @param int $courseId
     * @param array $params
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getList(int $courseId, array $params = [])
    {
        return AppCourseChapter::with('liveContent:id,chapter_id,live_room_id,live_status')
            ->select([
                'chapter_id', 'course_id', 'chapter_no', 'chapter_title',
                'is_free', 'chapter_start_time', 'chapter_end_time',
                'sort_order', 'status', 'created_at',
            ])
            ->byCourse($courseId)
            ->orderBy('sort_order', 'asc')
            ->orderBy('chapter_id', 'asc')
            ->paginate($params['pageSize'] ?? 20);
    }

    /**
     * 获取直播课章节详情
     *
     * @param int $chapterId
     * @return AppCourseChapter
     */
    public function getDetail(int $chapterId)
    {
        return AppCourseChapter::with('liveContent:id,chapter_id,live_room_id,live_status')
            ->select([
                'chapter_id', 'course_id', 'chapter_no', 'chapter_title',
                'is_free', 'chapter_start_time', 'chapter_end_time',
                'sort_order', 'status', 'created_at', 'updated_at',
            ])
            ->findOrFail($chapterId);
    }

    /**
     * 新增直播课章节
     *
     * @param int $courseId
     * @param array $data
     * @return AppCourseChapter
     */
    public function store(int $courseId, array $data)
    {
        return DB::transaction(function () use ($courseId, $data) {
            // 计算章节序号
            // $maxNo = AppCourseChapter::byCourse($courseId)->max('chapter_no');

            // 创建章节
            $chapter = AppCourseChapter::create([
                'course_id' => $courseId,
                // 'chapter_no' => ($maxNo ?? 0) + 1,
                'chapter_title' => $data['chapterTitle'],
                'is_free' => $data['isFree'],
                'chapter_start_time' => $data['liveStartTime'],
                'chapter_end_time' => $data['liveEndTime'],
                'sort_order' => 0,
                'status' => AppCourseChapter::STATUS_DRAFT,
            ]);

            // 创建直播内容记录
            AppChapterContentLive::create([
                'chapter_id' => $chapter->chapter_id,
                'live_room_id' => $data['roomId'],
                'live_start_time' => $data['liveStartTime'],
                'live_end_time' => $data['liveEndTime'],
            ]);

            Log::info('直播课章节创建成功', [
                'course_id' => $courseId,
                'chapter_id' => $chapter->chapter_id,
                'room_id' => $data['roomId'],
            ]);

            return $chapter;
        });
    }

    /**
     * 更新直播课章节
     *
     * @param int $chapterId
     * @param array $data
     * @return AppCourseChapter
     */
    public function update(int $chapterId, array $data)
    {
        return DB::transaction(function () use ($chapterId, $data) {
            $chapter = AppCourseChapter::findOrFail($chapterId);

            // 更新章节
            $chapter->update([
                'chapter_title' => $data['chapterTitle'],
                'is_free' => $data['isFree'],
                'chapter_start_time' => $data['liveStartTime'],
                'chapter_end_time' => $data['liveEndTime'],
            ]);

            // 更新或创建直播内容记录
            AppChapterContentLive::updateOrCreate(
                ['chapter_id' => $chapterId],
                [
                    'live_room_id' => $data['roomId'],
                    'live_start_time' => $data['liveStartTime'],
                    'live_end_time' => $data['liveEndTime'],
                ]
            );

            Log::info('直播课章节更新成功', [
                'chapter_id' => $chapterId,
                'room_id' => $data['roomId'],
            ]);

            return $chapter->fresh(['liveContent']);
        });
    }

    /**
     * 删除直播课章节
     *
     * @param int $chapterId
     * @return void
     */
    public function destroy(int $chapterId)
    {
        DB::transaction(function () use ($chapterId) {
            $chapter = AppCourseChapter::findOrFail($chapterId);

            // 删除直播内容
            AppChapterContentLive::where('chapter_id', $chapterId)->delete();

            // 删除章节
            $chapter->delete();

            Log::info('直播课章节删除成功', [
                'chapter_id' => $chapterId,
            ]);
        });
    }
}
