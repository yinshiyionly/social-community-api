<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Resources\App\AppApiResponse;
use App\Models\App\AppMemberPoint;
use App\Services\App\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * 任务中心控制器
 */
class TaskController extends Controller
{
    /**
     * @var TaskService
     */
    protected $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    /**
     * 获取任务中心数据
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function center(Request $request): JsonResponse
    {
        $memberId = $request->attributes->get('member_id');
        $tab = $request->input('tab', 'newbie');

        // 校验 tab 值
        if (!in_array($tab, ['newbie', 'daily'])) {
            $tab = 'newbie';
        }

        try {
            $data = $this->taskService->getTaskCenter($memberId, $tab);

            return AppApiResponse::success(['data' => $data]);
        } catch (\Exception $e) {
            Log::error('获取任务中心数据失败', [
                'member_id' => $memberId,
                'tab' => $tab,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }
}
