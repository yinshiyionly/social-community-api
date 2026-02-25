<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Resources\App\AppApiResponse;
use App\Http\Resources\App\PointLogListResource;
use App\Models\App\AppMemberPoint;
use App\Models\App\AppMemberPointLog;
use App\Services\App\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PointController extends Controller
{
    /**
     * 积分总览
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function overview(Request $request): JsonResponse
    {
        $memberId = $request->attributes->get('member_id');

        try {
            $account = AppMemberPoint::getOrCreate($memberId);

            return AppApiResponse::success([
                'data' => [
                    'availablePoints' => $account->available_points,
                    'totalPoints' => $account->total_points,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('获取积分总览失败', [
                'member_id' => $memberId,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 积分明细列表（普通分页）
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logs(Request $request): JsonResponse
    {
        $memberId = $request->attributes->get('member_id');
        $pageSize = (int)$request->input('pageSize', 20);

        try {
            $paginator = AppMemberPointLog::byMember($memberId)
                ->select([
                    'log_id',
                    'title',
                    'change_type',
                    'change_value',
                    'created_at',
                ])
                ->orderBy('log_id', 'desc')
                ->paginate($pageSize);

            return AppApiResponse::paginate($paginator, PointLogListResource::class);
        } catch (\Exception $e) {
            Log::error('获取积分明细失败', [
                'member_id' => $memberId,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 新人任务列表（成长任务）
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function growthTasks(Request $request): JsonResponse
    {
        $memberId = $request->attributes->get('member_id');

        try {
            $taskService = new TaskService();
            $list = $taskService->getGrowthTaskList($memberId);

            return AppApiResponse::success(['data' => $list]);
        } catch (\Exception $e) {
            Log::error('获取新人任务列表失败', [
                'member_id' => $memberId,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }
}
