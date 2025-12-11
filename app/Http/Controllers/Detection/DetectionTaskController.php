<?php

declare(strict_types=1);

namespace App\Http\Controllers\Detection;

use App\Http\Controllers\Controller;
use App\Http\Requests\Detection\Task\CreateDetectionTaskRequest;
use App\Http\Requests\Detection\Task\UpdateDetectionTaskRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\Detection\Task\DetectionTaskItemResource;
use App\Http\Resources\Detection\Task\DetectionTaskListResource;
use App\Services\Detection\Task\DetectionTaskService;
use Illuminate\Support\Facades\Request;

/**
 * 监测任务控制器
 */
class DetectionTaskController extends Controller
{
    private DetectionTaskService $detectionTaskService;

    public function __construct(DetectionTaskService $detectionTaskService)
    {
        $this->detectionTaskService = $detectionTaskService;
    }

    /**
     * 获取监测任务列表
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $result = $this->detectionTaskService->getList($request->all());

        return ApiResponse::paginate($result, DetectionTaskListResource::class);
    }

    /**
     * 获取监测任务详情
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $id)
    {
        $item = $this->detectionTaskService->getById($id);

        return ApiResponse::resource($item, DetectionTaskItemResource::class);
    }

    /**
     *
     *
     * @param CreateDetectionTaskRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CreateDetectionTaskRequest $request)
    {
        $this->detectionTaskService->create($request->validated());

        return ApiResponse::created();
    }

    public function update(UpdateDetectionTaskRequest $request)
    {
        $id = $request->get('id', 0);
        $this->detectionTaskService->update($id, $request->validated());

        return ApiResponse::updated();
    }

    public function destroy(int $id)
    {
        $this->detectionTaskService->delete($id);

        return ApiResponse::deleted();
    }
}
