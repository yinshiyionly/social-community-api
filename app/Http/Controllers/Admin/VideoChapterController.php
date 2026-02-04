<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VideoChapterStoreRequest;
use App\Http\Requests\Admin\VideoChapterUpdateRequest;
use App\Http\Requests\Admin\ChapterStatusRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\Admin\VideoChapterResource;
use App\Http\Resources\Admin\VideoChapterListResource;
use App\Models\App\AppCourseBase;
use App\Services\Admin\VideoChapterService;
use App\Services\Admin\CourseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VideoChapterController extends Controller
{
    /**
     * @var VideoChapterService
     */
    protected $chapterService;

    /**
     * @var CourseService
     */
    protected $courseService;

    public function __construct(VideoChapterService $chapterService, CourseService $courseService)
    {
        $this->chapterService = $chapterService;
        $this->courseService = $courseService;
    }

    /**
     * 章节列表（分页）
     *
     * @param Request $request
     * @param int $courseId
     * @return \Illuminate\Http\JsonResponse
     */
    public function list(Request $request, $courseId)
    {
        // 验证课程是否存在且为录播课
        $course = AppCourseBase::find($courseId);
        if (!$course) {
            return ApiResponse::error('课程不存在');
        }
        if ($course->play_type !== AppCourseBase::PLAY_TYPE_VIDEO) {
            return ApiResponse::error('该课程不是录播课');
        }

        $filters = [
            'chapterTitle' => $request->input('chapterTitle'),
            'status' => $request->input('status'),
            'isFree' => $request->input('isFree'),
        ];

        $pageNum = (int) $request->input('pageNum', 1);
        $pageSize = (int) $request->input('pageSize', 10);

        $paginator = $this->chapterService->getList((int) $courseId, $filters, $pageNum, $pageSize);

        return ApiResponse::paginate($paginator, VideoChapterListResource::class, '查询成功');
    }

    /**
     * 章节列表（全部，用于排序）
     *
     * @param int $courseId
     * @return \Illuminate\Http\JsonResponse
     */
    public function all($courseId)
    {
        $course = AppCourseBase::find($courseId);
        if (!$course) {
            return ApiResponse::error('课程不存在');
        }

        $chapters = $this->chapterService->getAllByCourse((int) $courseId);

        return ApiResponse::collection($chapters, VideoChapterListResource::class, '查询成功');
    }

    /**
     * 章节详情
     *
     * @param int $chapterId
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($chapterId)
    {
        $chapter = $this->chapterService->getDetail((int) $chapterId);

        if (!$chapter) {
            return ApiResponse::error('章节不存在');
        }

        return ApiResponse::resource($chapter, VideoChapterResource::class, '查询成功');
    }

    /**
     * 新增章节
     *
     * @param VideoChapterStoreRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(VideoChapterStoreRequest $request)
    {
        try {
            $courseId = $request->input('courseId');

            // 验证课程是否存在且为录播课
            $course = AppCourseBase::find($courseId);
            if (!$course) {
                return ApiResponse::error('课程不存在');
            }
            if ($course->play_type !== AppCourseBase::PLAY_TYPE_VIDEO) {
                return ApiResponse::error('该课程不是录播课，无法添加视频章节');
            }

            $data = [
                'courseId' => $courseId,
                'chapterNo' => $request->input('chapterNo'),
                'chapterTitle' => $request->input('chapterTitle'),
                'chapterSubtitle' => $request->input('chapterSubtitle'),
                'coverImage' => $request->input('coverImage'),
                'brief' => $request->input('brief'),
                'isFree' => $request->input('isFree'),
                'isPreview' => $request->input('isPreview'),
                'unlockType' => $request->input('unlockType'),
                'unlockDays' => $request->input('unlockDays'),
                'unlockDate' => $request->input('unlockDate'),
                'unlockTime' => $request->input('unlockTime'),
                'minLearnTime' => $request->input('minLearnTime'),
                'allowSkip' => $request->input('allowSkip'),
                'allowSpeed' => $request->input('allowSpeed'),
                'sortOrder' => $request->input('sortOrder'),
                // 视频内容
                'videoUrl' => $request->input('videoUrl'),
                'videoId' => $request->input('videoId'),
                'videoSource' => $request->input('videoSource'),
                'duration' => $request->input('duration'),
                'width' => $request->input('width'),
                'height' => $request->input('height'),
                'fileSize' => $request->input('fileSize'),
                'videoCoverImage' => $request->input('videoCoverImage'),
                'qualityList' => $request->input('qualityList'),
                'subtitles' => $request->input('subtitles'),
                'attachments' => $request->input('attachments'),
                'allowDownload' => $request->input('allowDownload'),
                'drmEnabled' => $request->input('drmEnabled'),
            ];

            $chapter = $this->chapterService->create($data);

            return ApiResponse::success(['chapterId' => $chapter->chapter_id], '新增成功');
        } catch (\Exception $e) {
            Log::error('新增录播课章节失败', [
                'action' => 'store',
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 更新章节
     *
     * @param VideoChapterUpdateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(VideoChapterUpdateRequest $request)
    {
        try {
            $chapterId = (int) $request->input('chapterId');

            $data = [
                'chapterNo' => $request->input('chapterNo'),
                'chapterTitle' => $request->input('chapterTitle'),
                'chapterSubtitle' => $request->input('chapterSubtitle'),
                'coverImage' => $request->input('coverImage'),
                'brief' => $request->input('brief'),
                'isFree' => $request->input('isFree'),
                'isPreview' => $request->input('isPreview'),
                'unlockType' => $request->input('unlockType'),
                'unlockDays' => $request->input('unlockDays'),
                'unlockDate' => $request->input('unlockDate'),
                'unlockTime' => $request->input('unlockTime'),
                'minLearnTime' => $request->input('minLearnTime'),
                'allowSkip' => $request->input('allowSkip'),
                'allowSpeed' => $request->input('allowSpeed'),
                'sortOrder' => $request->input('sortOrder'),
                'status' => $request->input('status'),
                // 视频内容
                'videoUrl' => $request->input('videoUrl'),
                'videoId' => $request->input('videoId'),
                'videoSource' => $request->input('videoSource'),
                'duration' => $request->input('duration'),
                'width' => $request->input('width'),
                'height' => $request->input('height'),
                'fileSize' => $request->input('fileSize'),
                'videoCoverImage' => $request->input('videoCoverImage'),
                'qualityList' => $request->input('qualityList'),
                'subtitles' => $request->input('subtitles'),
                'attachments' => $request->input('attachments'),
                'allowDownload' => $request->input('allowDownload'),
                'drmEnabled' => $request->input('drmEnabled'),
            ];

            $result = $this->chapterService->update($chapterId, $data);

            if (!$result) {
                return ApiResponse::error('章节不存在');
            }

            return ApiResponse::success([], '修改成功');
        } catch (\Exception $e) {
            Log::error('更新录播课章节失败', [
                'action' => 'update',
                'chapter_id' => $request->input('chapterId'),
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 删除章节（支持批量）
     *
     * @param string $chapterIds
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($chapterIds)
    {
        try {
            $ids = array_map('intval', explode(',', $chapterIds));

            $deletedCount = $this->chapterService->delete($ids);

            if ($deletedCount > 0) {
                return ApiResponse::success([], '删除成功');
            } else {
                return ApiResponse::error('删除失败，章节不存在');
            }
        } catch (\Exception $e) {
            Log::error('删除录播课章节失败', [
                'action' => 'destroy',
                'chapter_ids' => $chapterIds,
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 修改章节状态
     *
     * @param ChapterStatusRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changeStatus(ChapterStatusRequest $request)
    {
        try {
            $chapterId = (int) $request->input('chapterId');
            $status = (int) $request->input('status');

            $result = $this->chapterService->changeStatus($chapterId, $status);

            if (!$result) {
                return ApiResponse::error('章节不存在');
            }

            return ApiResponse::success([], '修改成功');
        } catch (\Exception $e) {
            Log::error('修改章节状态失败', [
                'action' => 'changeStatus',
                'chapter_id' => $request->input('chapterId'),
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 批量更新排序
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function batchSort(Request $request)
    {
        try {
            $sortData = $request->input('sortData', []);

            if (empty($sortData)) {
                return ApiResponse::error('排序数据不能为空');
            }

            $this->chapterService->batchUpdateSort($sortData);

            return ApiResponse::success([], '排序成功');
        } catch (\Exception $e) {
            Log::error('批量更新章节排序失败', [
                'action' => 'batchSort',
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }
}
