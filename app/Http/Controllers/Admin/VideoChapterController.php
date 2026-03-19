<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VideoChapterCopyRequest;
use App\Http\Requests\Admin\VideoChapterStoreRequest;
use App\Http\Requests\Admin\VideoChapterUpdateRequest;
use App\Http\Resources\Admin\VideoChapterListResource;
use App\Http\Resources\Admin\VideoChapterResource;
use App\Http\Resources\ApiResponse;
use App\Models\App\AppCourseChapter;
use App\Services\Admin\VideoChapterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * 后台录播课章节管理控制器。
 *
 * 职责：
 * 1. 提供录播章节列表、详情、创建、更新、删除等管理端接口；
 * 2. 统一返回章节相关常量选项，降低前端硬编码风险；
 * 3. 仅做参数编排与响应封装，业务落库由 Service 负责。
 */
class VideoChapterController extends Controller
{
    protected $videoChapterService;

    public function __construct(VideoChapterService $videoChapterService)
    {
        $this->videoChapterService = $videoChapterService;
    }

    /**
     * 录播章节常量选项（是否免费、解锁类型、状态）。
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function constants()
    {
        $data = [
            'isFreeOptions' => AppCourseChapter::getIsFreeOptions(),
            'unlockTypeOptions' => AppCourseChapter::getUnlockTypeOptions(),
            'statusOptions' => AppCourseChapter::getStatusOptions(),
        ];

        return ApiResponse::success(['data' => $data], '查询成功');
    }

    /**
     * 章节列表（分页）
     */
    public function list(Request $request, int $courseId)
    {
        $paginator = $this->videoChapterService->getList($courseId, $request->all());
        return ApiResponse::paginate($paginator, VideoChapterListResource::class);
    }

    /**
     * 章节列表（全部，用于排序）
     */
    public function all(int $courseId)
    {
        $chapters = $this->videoChapterService->getAll($courseId);
        return ApiResponse::collection($chapters, VideoChapterListResource::class);
    }


    /**
     * 章节详情
     */
    public function show(int $chapterId)
    {
        $chapter = $this->videoChapterService->getDetail($chapterId);
        if (!$chapter) {
            return ApiResponse::notFound('章节不存在');
        }
        return ApiResponse::resource($chapter, VideoChapterResource::class);
    }

    /**
     * 创建章节
     */
    public function store(VideoChapterStoreRequest $request)
    {
        $this->videoChapterService->store($request->validated());
        return ApiResponse::success(null, '操作成功');
    }

    /**
     * 更新章节
     */
    public function update(VideoChapterUpdateRequest $request)
    {
        $this->videoChapterService->update($request->validated());
        return ApiResponse::success(null, '操作成功');
    }

    /**
     * 批量排序
     */
    public function batchSort(Request $request)
    {
        $items = $request->input('items', []);
        if (empty($items)) {
            return ApiResponse::error('排序数据不能为空');
        }

        $this->videoChapterService->batchSort($items);
        return ApiResponse::success(null, '操作成功');
    }

    /**
     * 删除章节（单个）
     */
    public function destroy(int $chapterId)
    {
        $this->videoChapterService->destroy($chapterId);
        return ApiResponse::success(null, '操作成功');
    }

    /**
     * 复制录播章节（仅同课程内复制）。
     *
     * 规则：
     * 1. 新章节自动追加到课程末尾（chapter_no/sort_order 递增）；
     * 2. 新章节状态重置为草稿，并同步复制视频内容映射；
     * 3. 缺失视频内容时拒绝复制，避免生成不可用章节。
     *
     * @param VideoChapterCopyRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function copy(VideoChapterCopyRequest $request)
    {
        try {
            $chapterId = (int)$request->input('chapterId');
            $chapter = $this->videoChapterService->copy($chapterId);

            return ApiResponse::success([
                'data' => [
                    'chapterId' => $chapter->chapter_id,
                    'courseId' => $chapter->course_id,
                ],
            ], '复制成功');
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage());
        } catch (\Exception $e) {
            Log::error('复制录播章节失败', [
                'action' => 'copy',
                'chapter_id' => $request->input('chapterId'),
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }
}
