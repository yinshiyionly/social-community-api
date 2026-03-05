<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LiveChapterStoreRequest;
use App\Http\Requests\Admin\LiveChapterUpdateRequest;
use App\Http\Resources\Admin\LiveChapterListResource;
use App\Http\Resources\Admin\LiveChapterResource;
use App\Http\Resources\ApiResponse;
use App\Services\Admin\LiveChapterService;
use Illuminate\Http\Request;

class LiveChapterController extends Controller
{
    protected $liveChapterService;

    public function __construct(LiveChapterService $liveChapterService)
    {
        $this->liveChapterService = $liveChapterService;
    }

    /**
     * 直播课章节列表
     */
    public function index(Request $request, int $courseId)
    {
        $params = $request->only(['pageSize']);
        $paginator = $this->liveChapterService->getList($courseId, $params);

        return ApiResponse::paginate($paginator, LiveChapterListResource::class);
    }

    /**
     * 直播课章节详情
     */
    public function show(int $courseId, int $chapterId)
    {
        $chapter = $this->liveChapterService->getDetail($chapterId);

        return ApiResponse::resource($chapter, LiveChapterResource::class);
    }

    /**
     * 新增直播课章节
     */
    public function store(LiveChapterStoreRequest $request)
    {
        $data = $request->validated();
        $chapter = $this->liveChapterService->store($data['courseId'], $data);

        return ApiResponse::created();
    }

    /**
     * 更新直播课章节
     */
    public function update(LiveChapterUpdateRequest $request)
    {
        $data = $request->validated();
        $this->liveChapterService->update($data['chapterId'], $data);

        return ApiResponse::updated();
    }

    /**
     * 删除直播课章节
     */
    public function destroy(int $courseId, int $chapterId)
    {
        $this->liveChapterService->destroy($chapterId);

        return ApiResponse::deleted();
    }
}
