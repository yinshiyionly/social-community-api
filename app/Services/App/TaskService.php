<?php

namespace App\Services\App;

use App\Models\App\AppMemberGrowthTask;
use App\Models\App\AppMemberPoint;
use App\Models\App\AppMemberPointLog;
use App\Models\App\AppMemberTaskRecord;
use App\Models\App\AppPointTask;

/**
 * 任务中心服务
 */
class TaskService
{
    /**
     * 获取任务中心数据
     *
     * @param int $memberId
     * @param string $tab newbie|daily
     * @return array
     */
    public function getTaskCenter(int $memberId, string $tab): array
    {
        $account = AppMemberPoint::getOrCreate($memberId);

        $tabs = [
            ['label' => '新人任务', 'value' => 'newbie'],
            ['label' => '日常任务', 'value' => 'daily'],
        ];

        if ($tab === 'daily') {
            $list = $this->buildDailyTaskItems($memberId);
        } else {
            $list = $this->buildGrowthTaskItems($memberId);
        }

        return [
            'scoreBalance' => $account->available_points,
            'tabs' => $tabs,
            'currentTab' => $tab,
            'list' => $list,
        ];
    }

    /**
     * 获取学分明细（分页）
     *
     * @param int $memberId
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getScoreDetail(int $memberId, int $page, int $pageSize): array
    {
        $account = AppMemberPoint::getOrCreate($memberId);

        // 累计获取学分
        $totalEarned = AppMemberPointLog::byMember($memberId)
            ->earned()
            ->sum('change_value');

        // 分页查询明细
        $paginator = AppMemberPointLog::byMember($memberId)
            ->select(['log_id', 'title', 'change_value', 'created_at'])
            ->orderBy('log_id', 'desc')
            ->paginate($pageSize, ['*'], 'page', $page);

        $list = [];
        foreach ($paginator->items() as $log) {
            $list[] = [
                'id' => $log->log_id,
                'title' => $log->title,
                'time' => $log->created_at ? $log->created_at->format('Y.m.d H:i:s') : '',
                'scoreChange' => $log->change_value,
            ];
        }

        return [
            'scoreBalance' => $account->available_points,
            'totalEarned' => (int) $totalEarned,
            'list' => $list,
            'total' => $paginator->total(),
            'page' => $paginator->currentPage(),
            'pageSize' => $paginator->perPage(),
        ];
    }

    /**
     * 构建新人任务列表项（任务中心格式）
     *
     * @param int $memberId
     * @return array
     */
    private function buildGrowthTaskItems(int $memberId): array
    {
        $tasks = AppPointTask::enabled()
            ->active()
            ->byType(AppPointTask::TYPE_GROWTH)
            ->select(['task_id', 'task_code', 'task_name', 'point_value', 'sort_order'])
            ->orderBy('sort_order')
            ->get();

        if ($tasks->isEmpty()) {
            return [];
        }

        $memberTasks = AppMemberGrowthTask::byMember($memberId)
            ->select(['task_code', 'is_completed'])
            ->get()
            ->keyBy('task_code');

        $list = [];
        foreach ($tasks as $task) {
            $memberTask = $memberTasks->get($task->task_code);
            $isDone = $memberTask && $memberTask->is_completed;

            $list[] = [
                'id' => $task->task_id,
                'title' => $task->task_name,
                'rewardScore' => $task->point_value,
                'status' => $isDone ? 'done' : 'todo',
                'actionText' => $isDone ? '已完成' : '去完成',
                'sortNo' => $task->sort_order,
            ];
        }

        return $list;
    }

    /**
     * 构建日常任务列表项（任务中心格式）
     *
     * @param int $memberId
     * @return array
     */
    private function buildDailyTaskItems(int $memberId): array
    {
        $tasks = AppPointTask::enabled()
            ->active()
            ->byType(AppPointTask::TYPE_DAILY)
            ->select(['task_id', 'task_code', 'task_name', 'point_value', 'daily_limit', 'icon', 'sort_order'])
            ->orderBy('sort_order')
            ->get();

        if ($tasks->isEmpty()) {
            return [];
        }

        $today = date('Y-m-d');
        $taskCodes = $tasks->pluck('task_code')->toArray();

        $todayCounts = AppMemberTaskRecord::byMember($memberId)
            ->byDate($today)
            ->whereIn('task_code', $taskCodes)
            ->selectRaw('task_code, count(*) as completed_count')
            ->groupBy('task_code')
            ->pluck('completed_count', 'task_code');

        $list = [];
        foreach ($tasks as $task) {
            $completedCount = (int) ($todayCounts->get($task->task_code, 0));
            $isDone = $task->daily_limit > 0 && $completedCount >= $task->daily_limit;

            $list[] = [
                'id' => $task->task_id,
                'title' => $task->task_name,
                'rewardScore' => $task->point_value,
                'status' => $isDone ? 'done' : 'todo',
                'actionText' => $isDone ? '已完成' : '去完成',
                'avatar' => $task->icon,
            ];
        }

        return $list;
    }

    /**
     * 获取新人任务列表（成长任务 + 用户完成状态）
     *
     * @param int $memberId
     * @return array
     */
    public function getGrowthTaskList(int $memberId): array
    {
        // 获取所有启用的成长任务配置
        $tasks = AppPointTask::enabled()
            ->active()
            ->byType(AppPointTask::TYPE_GROWTH)
            ->select([
                'task_id',
                'task_code',
                'task_name',
                'point_value',
                'icon',
                'description',
                'jump_url',
                'sort_order',
            ])
            ->orderBy('sort_order')
            ->get();

        if ($tasks->isEmpty()) {
            return [];
        }

        // 获取用户的成长任务完成状态
        $memberTasks = AppMemberGrowthTask::byMember($memberId)
            ->select(['task_code', 'is_completed', 'complete_time'])
            ->get()
            ->keyBy('task_code');

        // 组装数据
        $list = [];
        foreach ($tasks as $task) {
            $memberTask = $memberTasks->get($task->task_code);
            $list[] = [
                'taskId' => $task->task_id,
                'taskCode' => $task->task_code,
                'taskName' => $task->task_name,
                'pointValue' => $task->point_value,
                'icon' => $task->icon,
                'description' => $task->description,
                'jumpUrl' => $task->jump_url,
                'isCompleted' => $memberTask ? $memberTask->is_completed : 0,
                'completeTime' => $memberTask && $memberTask->complete_time
                    ? $memberTask->complete_time->format('Y-m-d H:i:s')
                    : null,
            ];
        }

        return $list;
    }

    /**
     * 获取日常任务列表（日常任务 + 今日完成状态）
     *
     * @param int $memberId
     * @return array
     */
    public function getDailyTaskList(int $memberId): array
    {
        // 获取所有启用的日常任务配置
        $tasks = AppPointTask::enabled()
            ->active()
            ->byType(AppPointTask::TYPE_DAILY)
            ->select([
                'task_id',
                'task_code',
                'task_name',
                'point_value',
                'daily_limit',
                'icon',
                'description',
                'jump_url',
                'sort_order',
            ])
            ->orderBy('sort_order')
            ->get();

        if ($tasks->isEmpty()) {
            return [];
        }

        // 批量获取用户今日各任务完成次数
        $today = date('Y-m-d');
        $taskCodes = $tasks->pluck('task_code')->toArray();

        $todayCounts = AppMemberTaskRecord::byMember($memberId)
            ->byDate($today)
            ->whereIn('task_code', $taskCodes)
            ->selectRaw('task_code, count(*) as completed_count')
            ->groupBy('task_code')
            ->pluck('completed_count', 'task_code');

        // 组装数据
        $list = [];
        foreach ($tasks as $task) {
            $completedCount = (int) ($todayCounts->get($task->task_code, 0));
            $dailyLimit = $task->daily_limit;
            // 达到每日上限则为已完成
            $isCompleted = $dailyLimit > 0 && $completedCount >= $dailyLimit;

            $list[] = [
                'taskId' => $task->task_id,
                'taskCode' => $task->task_code,
                'taskName' => $task->task_name,
                'pointValue' => $task->point_value,
                'dailyLimit' => $dailyLimit,
                'completedCount' => $completedCount,
                'isCompleted' => $isCompleted ? 1 : 0,
                'icon' => $task->icon,
                'description' => $task->description,
                'jumpUrl' => $task->jump_url,
            ];
        }

        return $list;
    }
}
