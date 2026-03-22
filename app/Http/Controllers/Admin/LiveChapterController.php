<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LiveChapterStoreRequest;
use App\Http\Requests\Admin\LiveChapterUpdateRequest;
use App\Http\Resources\Admin\LiveCourseSheetChapterResource;
use App\Http\Resources\Admin\LiveChapterListResource;
use App\Http\Resources\Admin\LiveChapterResource;
use App\Http\Resources\ApiResponse;
use App\Models\App\AppCourseChapter;
use App\Services\Admin\LiveChapterService;
use Illuminate\Http\Request;

/**
 * 后台直播课章节管理控制器。
 *
 * 职责：
 * 1. 提供直播章节列表、详情、创建、更新、删除等管理端接口；
 * 2. 提供章节常量选项，避免前端硬编码章节枚举；
 * 3. 仅负责参数编排与响应封装，双表写入与元数据回填由 Service 处理。
 */
class LiveChapterController extends Controller
{
    protected $liveChapterService;

    public function __construct(LiveChapterService $liveChapterService)
    {
        $this->liveChapterService = $liveChapterService;
    }

    /**
     * 直播章节常量选项（是否免费、解锁类型、状态）。
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function constants()
    {
        $data = [
            'isFreeOptions'     => AppCourseChapter::getIsFreeOptions(),
            'unlockTypeOptions' => AppCourseChapter::getUnlockTypeOptions(),
            'statusOptions'     => AppCourseChapter::getStatusOptions(),
        ];

        return ApiResponse::success(['data' => $data], '查询成功');
    }

    /**
     * 直播章节列表（分页）。
     *
     * @param Request $request
     * @param int $courseId 课程ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function list(Request $request, int $courseId)
    {
        $paginator = $this->liveChapterService->getList($courseId, $request->all());

        return ApiResponse::paginate($paginator, LiveChapterListResource::class);
    }

    /**
     * 直播章节列表（全部，用于直播课课程表）。
     *
     * 返回约定：
     * 1. 保留章节基础字段，兼容章节管理页与课表页共享数据口径；
     * 2. 直播字段采用平铺结构，便于前端课程表直接渲染；
     * 3. 仅返回直播课课程章节，课程不存在或非直播课时返回空列表。
     *
     * @param int $courseId 课程ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function all(int $courseId)
    {
        $chapters = $this->liveChapterService->getAll($courseId);

        return ApiResponse::collection($chapters, LiveCourseSheetChapterResource::class);
    }

    /**
     * 直播章节详情。
     *
     * @param int $chapterId 章节ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $chapterId)
    {
        $chapter = $this->liveChapterService->getDetail($chapterId);
        if (!$chapter) {
            return ApiResponse::notFound('章节不存在');
        }

        return ApiResponse::resource($chapter, LiveChapterResource::class);
    }

    /**
     * 创建直播章节。
     *
     * 关键规则：
     * 1. 创建参数与更新参数保持一致，避免双套规则带来联调歧义；
     * 2. 创建时在事务内联动写章节基础表与直播章节内容表；
     * 3. 前端仅传 liveRoomId，直播元数据由服务层回填。
     *
     * @param LiveChapterStoreRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(LiveChapterStoreRequest $request)
    {
        $this->liveChapterService->store($request->validated());

        return ApiResponse::success(null, '操作成功');
    }

    /**
     * 更新直播章节。
     *
     * 关键规则：
     * 1. status 在本接口中直接更新，不再提供独立改状态接口；
     * 2. 更新时按最新 liveRoomId 重新回填最小直播元数据；
     * 3. 章节仅允许在所属课程内更新，避免跨课程误写。
     *
     * @param LiveChapterUpdateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(LiveChapterUpdateRequest $request)
    {
        $this->liveChapterService->update($request->validated());

        return ApiResponse::success(null, '操作成功');
    }

    /**
     * 删除直播章节（单个）。
     *
     * @param int $chapterId 章节ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $chapterId)
    {
        $this->liveChapterService->destroy($chapterId);

        return ApiResponse::success(null, '操作成功');
    }
}
