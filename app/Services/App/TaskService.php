<?php

namespace App\Services\App;

use App\Models\App\AppMemberGrowthTask;
use App\Models\App\AppMemberTaskRecord;
use App\Models\App\AppPointTask;

/**
 * 任务中心服务
 */
class TaskService
{
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
