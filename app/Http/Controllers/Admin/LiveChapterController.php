<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LiveChapterStoreRequest;
use App\Http\Requests\Admin\LiveChapterUpdateRequest;
use App\Http\Requests\Admin\LiveChapterStatusRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\Admin\LiveChapterResource;
use App\Http\Resources\Admin\LiveChapterListResource;
use App\Services\Admin\LiveChapterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LiveChapterController extends Controller
{
    /**
     * @var LiveChapterService
     */
    protected $liveChapterService;

    /**
     * LiveChapterController constructor.
     *
     * @param LiveChapterService $liveChapterService
     */
    public function __construct(LiveChapterService $liveChapterService)
    {
        $this->liveChapterService = $liveChapterService;
    }

    /**
     * 直播课章节列表
     *
     * @param int $courseId
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function list($courseId, Request $request)
    {
        try {
            $filters = [
                'liveStatus' => $request->input('liveStatus'),
                'chapterTitle' => $request->input('chapterTitle'),
            ];

            $pageNum = (int) $request->input('pageNum', 1);
            $pageSize = (int) $request->input('pageSize', 10);

            $paginator = $this->liveChapterService->getList((int) $courseId, $filters, $pageNum, $pageSize);

            return ApiResponse::paginate($paginator, LiveChapterListResource::class, '查询成功');
        } catch (\Exception $e) {
            Log::error('直播课章节操作失败', [
                'action' => 'list',
                'course_id' => $courseId,
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 直播课章节详情
     *
     * @param int $chapterId
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($chapterId)
    {
        try {
            $chapter = $this->liveChapterService->getDetail((int) $chapterId);

            if (!$chapter) {
                return ApiResponse::error('直播课章节不存在');
            }

            return ApiResponse::resource($chapter, LiveChapterResource::class, '查询成功');
        } catch (\Exception $e) {
            Log::error('直播课章节操作失败', [
                'action' => 'show',
                'chapter_id' => $chapterId,
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 创建直播课章节
     *
     * @param LiveChapterStoreRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(LiveChapterStoreRequest $request)
    {
        try {
            $data = [
                'courseId' => $request->input('courseId'),
                'chapterTitle' => $request->input('chapterTitle'),
                'liveRoomId' => $request->input('liveRoomId'),
                'liveStartTime' => $request->input('liveStartTime'),
                'liveEndTime' => $request->input('liveEndTime'),
                'chapterNo' => $request->input('chapterNo'),
                'chapterSubtitle' => $request->input('chapterSubtitle'),
                'coverImage' => $request->input('coverImage'),
                'brief' => $request->input('brief'),
                'sortOrder' => $request->input('sortOrder'),
                'liveCover' => $request->input('liveCover'),
                'liveDuration' => $request->input('liveDuration'),
                'allowChat' => $request->input('allowChat'),
                'allowGift' => $request->input('allowGift'),
                'attachments' => $request->input('attachments'),
            ];

            $result = $this->liveChapterService->create($data);

            if (!$result['success']) {
                return ApiResponse::error($result['error']);
            }

            return ApiResponse::success([], '新增成功');
        } catch (\Exception $e) {
            Log::error('直播课章节操作失败', [
                'action' => 'store',
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 更新直播课章节
     *
     * @param LiveChapterUpdateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(LiveChapterUpdateRequest $request)
    {
        try {
            $chapterId = (int) $request->input('chapterId');

            $data = [
                'chapterTitle' => $request->input('chapterTitle'),
                'liveRoomId' => $request->input('liveRoomId'),
                'chapterNo' => $request->input('chapterNo'),
                'chapterSubtitle' => $request->input('chapterSubtitle'),
                'coverImage' => $request->input('coverImage'),
                'brief' => $request->input('brief'),
                'sortOrder' => $request->input('sortOrder'),
                'liveCover' => $request->input('liveCover'),
                'liveDuration' => $request->input('liveDuration'),
                'liveStartTime' => $request->input('liveStartTime'),
                'liveEndTime' => $request->input('liveEndTime'),
                'allowChat' => $request->input('allowChat'),
                'allowGift' => $request->input('allowGift'),
                'attachments' => $request->input('attachments'),
            ];

            $result = $this->liveChapterService->update($chapterId, $data);

            if (!$result['success']) {
                return ApiResponse::error($result['error']);
            }

            return ApiResponse::success([], '修改成功');
        } catch (\Exception $e) {
            Log::error('直播课章节操作失败', [
                'action' => 'update',
                'chapter_id' => $request->input('chapterId'),
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 删除直播课章节（支持批量）
     *
     * @param string $chapterIds 逗号分隔的章节ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($chapterIds)
    {
        try {
            $ids = array_map('intval', explode(',', $chapterIds));

            $result = $this->liveChapterService->delete($ids);

            if (!$result['success']) {
                return ApiResponse::error($result['error']);
            }

            return ApiResponse::success([], '删除成功');
        } catch (\Exception $e) {
            Log::error('直播课章节操作失败', [
                'action' => 'destroy',
                'chapter_ids' => $chapterIds,
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 修改直播课章节状态
     *
     * @param LiveChapterStatusRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changeStatus(LiveChapterStatusRequest $request)
    {
        try {
            $chapterId = (int) $request->input('chapterId');
            $status = (int) $request->input('status');

            $result = $this->liveChapterService->changeStatus($chapterId, $status);

            if (!$result) {
                return ApiResponse::error('直播课章节不存在');
            }

            return ApiResponse::success([], '修改成功');
        } catch (\Exception $e) {
            Log::error('直播课章节操作失败', [
                'action' => 'changeStatus',
                'chapter_id' => $request->input('chapterId'),
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 同步直播间状态
     *
     * @param int $chapterId
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncLiveStatus($chapterId)
    {
        try {
            $result = $this->liveChapterService->syncLiveStatus((int) $chapterId);

            if (!$result['success']) {
                return ApiResponse::error($result['error']);
            }

            return ApiResponse::success($result['data'], '同步成功');
        } catch (\Exception $e) {
            Log::error('直播课章节操作失败', [
                'action' => 'syncLiveStatus',
                'chapter_id' => $chapterId,
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 获取直播回放列表
     *
     * @param int $chapterId
     * @return \Illuminate\Http\JsonResponse
     */
    public function playbackList($chapterId)
    {
        try {
            $result = $this->liveChapterService->getPlaybackList((int) $chapterId);

            if (!$result['success']) {
                return ApiResponse::error($result['error']);
            }

            return ApiResponse::success(['list' => $result['data']], '查询成功');
        } catch (\Exception $e) {
            Log::error('直播课章节操作失败', [
                'action' => 'playbackList',
                'chapter_id' => $chapterId,
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 同步回放信息
     *
     * @param int $chapterId
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncPlayback($chapterId)
    {
        try {
            $result = $this->liveChapterService->syncPlayback((int) $chapterId);

            if (!$result['success']) {
                return ApiResponse::error($result['error']);
            }

            return ApiResponse::success([], '同步成功');
        } catch (\Exception $e) {
            Log::error('直播课章节操作失败', [
                'action' => 'syncPlayback',
                'chapter_id' => $chapterId,
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }
}
