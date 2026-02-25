<?php

namespace App\Services\App;

use App\Models\App\AppMemberGrowthTask;
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
}
