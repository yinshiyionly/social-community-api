<?php

declare(strict_types=1);

namespace App\Http\Controllers\Detection;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Detection\Task\CreateDetectionTaskRequest;
use App\Http\Requests\Detection\Task\UpdateDetectionTaskRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\Detection\Task\DetectionTaskItemResource;
use App\Http\Resources\Detection\Task\DetectionTaskListResource;
use App\Services\Detection\Task\DetectionTaskService;
use Illuminate\Http\Request;

/**
 * 监测任务控制器
 */
class DetectionTaskController extends Controller
{
    // 服务类
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
     * @param int $taskId
     * @return \Illuminate\Http\JsonResponse
     * @throws ApiException
     */
    public function show(int $taskId)
    {
        $item = $this->detectionTaskService->getById($taskId);

        return ApiResponse::resource($item, DetectionTaskItemResource::class);
    }

    /**
     * 创建任务
     *
     * @param CreateDetectionTaskRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function store(CreateDetectionTaskRequest $request)
    {
        $this->detectionTaskService->create($request->validated());

        return ApiResponse::created();
    }

    /**
     * 更新任务
     *
     * @param UpdateDetectionTaskRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function update(UpdateDetectionTaskRequest $request)
    {
        $taskId = $request->get('task_id', 0);
        $this->detectionTaskService->update((int)$taskId, $request->validated());

        return ApiResponse::updated();
    }

    /**
     * 任务开关
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function taskSwitch(Request $request)
    {
        $taskId = $request->get('task_id', 0);
        $switch = $request->get('switch', 0);
        // 关闭任务
        if ($switch == 0) {
            $this->detectionTaskService->closeAction($taskId);
            return ApiResponse::updated();
        }
        // 开启任务
        $this->detectionTaskService->openAction($taskId);
        return ApiResponse::updated();

    }

    /**
     * 删除任务
     *
     * @param int $taskId
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function destroy(int $taskId)
    {
        $this->detectionTaskService->delete($taskId);

        return ApiResponse::deleted();
    }
}
