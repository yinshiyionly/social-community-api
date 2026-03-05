<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VideoChapterStoreRequest;
use App\Http\Requests\Admin\VideoChapterUpdateRequest;
use App\Http\Resources\Admin\VideoChapterListResource;
use App\Http\Resources\Admin\VideoChapterResource;
use App\Http\Resources\ApiResponse;
use App\Services\Admin\VideoChapterService;
use Illuminate\Http\Request;

class VideoChapterController extends Controller
{
    protected $videoChapterService;

    public function __construct(VideoChapterService $videoChapterService)
    {
        $this->videoChapterService = $videoChapterService;
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
        $chapter = $this->videoChapterService->store($request->validated());
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
     * 更改状态
     */
    public function changeStatus(Request $request)
    {
        $chapterId = $request->input('chapterId');
        $status = $request->input('status');

        if (!$chapterId || !$status) {
            return ApiResponse::error('参数不完整');
        }

        $this->videoChapterService->changeStatus($chapterId, $status);
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
     * 删除章节
     */
    public function destroy(string $chapterIds)
    {
        $ids = array_map('intval', explode(',', $chapterIds));
        $this->videoChapterService->destroy($ids);
        return ApiResponse::success(null, '操作成功');
    }
}
