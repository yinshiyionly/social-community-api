<?php

namespace App\Services\App;

use App\Jobs\App\ProcessPointEarnJob;
use App\Jobs\App\ProcessPointConsumeJob;
use App\Models\App\AppMemberGrowthTask;
use App\Models\App\AppMemberPoint;
use App\Models\App\AppMemberTaskRecord;
use App\Models\App\AppMemberPointLog;
use App\Models\App\AppPointTask;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PointService
{
    /**
     * 触发任务积分获取（异步处理）
     *
     * @param int $memberId 用户ID
     * @param string $taskCode 任务编码
     * @param string|null $bizId 业务ID（用于防重）
     * @param string|null $clientIp 客户端IP
     * @return void
     */
    public function triggerTaskEarn(int $memberId, string $taskCode, ?string $bizId = null, ?string $clientIp = null): void
    {
        ProcessPointEarnJob::dispatch([
            'member_id' => $memberId,
            'task_code' => $taskCode,
            'biz_id' => $bizId,
            'client_ip' => $clientIp,
        ]);

        Log::info('积分获取任务已加入队列', [
            'member_id' => $memberId,
            'task_code' => $taskCode,
            'biz_id' => $bizId,
        ]);
    }

    /**
     * 触发积分消费（异步处理）
     *
     * @param int $memberId 用户ID
     * @param int $points 消费积分数
     * @param string $title 消费标题
     * @param string|null $orderNo 订单号
     * @param string|null $remark 备注
     * @param string|null $clientIp 客户端IP
     * @return void
     */
    public function triggerConsume(
        int $memberId,
        int $points,
        string $title,
        ?string $orderNo = null,
        ?string $remark = null,
        ?string $clientIp = null
    ): void {
        ProcessPointConsumeJob::dispatch([
            'member_id' => $memberId,
            'points' => $points,
            'title' => $title,
            'order_no' => $orderNo,
            'remark' => $remark,
            'client_ip' => $clientIp,
        ]);

        Log::info('积分消费任务已加入队列', [
            'member_id' => $memberId,
            'points' => $points,
            'order_no' => $orderNo,
        ]);
    }


    /**
     * 执行任务积分获取（同步，供 Job 调用）
     *
     * @param int $memberId 用户ID
     * @param string $taskCode 任务编码
     * @param string|null $bizId 业务ID
     * @param string|null $clientIp 客户端IP
     * @return array
     */
    public function processTaskEarn(int $memberId, string $taskCode, ?string $bizId = null, ?string $clientIp = null): array
    {
        // 获取任务配置
        $task = AppPointTask::getByCode($taskCode);
        if (!$task) {
            return ['success' => false, 'message' => '任务不存在或已禁用'];
        }

        // 根据任务类型处理
        if ($task->isGrowthTask()) {
            return $this->processGrowthTaskEarn($memberId, $task, $bizId, $clientIp);
        }

        return $this->processDailyTaskEarn($memberId, $task, $bizId, $clientIp);
    }

    /**
     * 处理日常任务积分获取
     *
     * @param int $memberId
     * @param AppPointTask $task
     * @param string|null $bizId
     * @param string|null $clientIp
     * @return array
     */
    protected function processDailyTaskEarn(int $memberId, AppPointTask $task, ?string $bizId, ?string $clientIp): array
    {
        // 检查今日完成次数
        $todayCount = AppMemberTaskRecord::getTodayCount($memberId, $task->task_code);
        if ($task->daily_limit > 0 && $todayCount >= $task->daily_limit) {
            return ['success' => false, 'message' => '今日任务次数已达上限'];
        }

        // 检查是否已完成该业务（防重）
        if ($bizId && AppMemberTaskRecord::hasCompletedBiz($memberId, $task->task_code, $bizId)) {
            return ['success' => false, 'message' => '该任务已完成'];
        }

        // 检查总次数限制
        if ($task->total_limit > 0) {
            $totalCount = AppMemberTaskRecord::getTotalCount($memberId, $task->task_code);
            if ($totalCount >= $task->total_limit) {
                return ['success' => false, 'message' => '任务总次数已达上限'];
            }
        }

        DB::beginTransaction();
        try {
            // 获取或创建积分账户
            $pointAccount = AppMemberPoint::getOrCreate($memberId);
            $beforePoints = $pointAccount->available_points;

            // 增加积分
            $pointAccount->addPoints($task->point_value);

            // 创建任务完成记录
            AppMemberTaskRecord::create([
                'member_id' => $memberId,
                'task_id' => $task->task_id,
                'task_code' => $task->task_code,
                'task_type' => $task->task_type,
                'point_value' => $task->point_value,
                'complete_date' => date('Y-m-d'),
                'complete_count' => $todayCount + 1,
                'biz_id' => $bizId,
            ]);

            // 创建积分日志
            $this->createPointLog([
                'member_id' => $memberId,
                'change_type' => AppMemberPointLog::CHANGE_TYPE_EARN,
                'change_value' => $task->point_value,
                'before_points' => $beforePoints,
                'after_points' => $pointAccount->available_points,
                'source_type' => AppMemberPointLog::SOURCE_TYPE_TASK,
                'source_id' => $bizId,
                'task_code' => $task->task_code,
                'title' => $task->task_name,
                'remark' => '完成日常任务获得积分',
                'client_ip' => $clientIp,
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => '积分获取成功',
                'data' => [
                    'point_value' => $task->point_value,
                    'available_points' => $pointAccount->available_points,
                ],
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('日常任务积分获取失败', [
                'member_id' => $memberId,
                'task_code' => $task->task_code,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }


    /**
     * 处理成长任务积分获取（只能完成一次）
     *
     * @param int $memberId
     * @param AppPointTask $task
     * @param string|null $bizId
     * @param string|null $clientIp
     * @return array
     */
    protected function processGrowthTaskEarn(int $memberId, AppPointTask $task, ?string $bizId, ?string $clientIp): array
    {
        // 检查是否已完成该成长任务
        if (AppMemberGrowthTask::isCompleted($memberId, $task->task_code)) {
            return ['success' => false, 'message' => '该成长任务已完成'];
        }

        DB::beginTransaction();
        try {
            // 获取或创建积分账户
            $pointAccount = AppMemberPoint::getOrCreate($memberId);
            $beforePoints = $pointAccount->available_points;

            // 增加积分
            $pointAccount->addPoints($task->point_value);

            // 获取或创建成长任务状态并标记完成
            $growthTask = AppMemberGrowthTask::getOrCreate($memberId, $task);
            $growthTask->markCompleted($task->point_value);

            // 创建任务完成记录
            AppMemberTaskRecord::create([
                'member_id' => $memberId,
                'task_id' => $task->task_id,
                'task_code' => $task->task_code,
                'task_type' => $task->task_type,
                'point_value' => $task->point_value,
                'complete_date' => date('Y-m-d'),
                'complete_count' => 1,
                'biz_id' => $bizId,
            ]);

            // 创建积分日志
            $this->createPointLog([
                'member_id' => $memberId,
                'change_type' => AppMemberPointLog::CHANGE_TYPE_EARN,
                'change_value' => $task->point_value,
                'before_points' => $beforePoints,
                'after_points' => $pointAccount->available_points,
                'source_type' => AppMemberPointLog::SOURCE_TYPE_TASK,
                'source_id' => $bizId,
                'task_code' => $task->task_code,
                'title' => $task->task_name,
                'remark' => '完成成长任务获得积分',
                'client_ip' => $clientIp,
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => '成长任务完成',
                'data' => [
                    'point_value' => $task->point_value,
                    'available_points' => $pointAccount->available_points,
                ],
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('成长任务积分获取失败', [
                'member_id' => $memberId,
                'task_code' => $task->task_code,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * 执行积分消费（同步，供 Job 调用）
     *
     * @param int $memberId 用户ID
     * @param int $points 消费积分数
     * @param string $title 消费标题
     * @param string|null $orderNo 订单号
     * @param string|null $remark 备注
     * @param string|null $clientIp 客户端IP
     * @return array
     */
    public function processConsume(
        int $memberId,
        int $points,
        string $title,
        ?string $orderNo = null,
        ?string $remark = null,
        ?string $clientIp = null
    ): array {
        if ($points <= 0) {
            return ['success' => false, 'message' => '消费积分数必须大于0'];
        }

        $pointAccount = AppMemberPoint::getOrCreate($memberId);

        // 检查积分是否足够
        if (!$pointAccount->hasEnoughPoints($points)) {
            return ['success' => false, 'message' => '积分不足'];
        }

        DB::beginTransaction();
        try {
            $beforePoints = $pointAccount->available_points;

            // 扣减积分
            $pointAccount->usePoints($points);

            // 创建积分日志
            $this->createPointLog([
                'member_id' => $memberId,
                'change_type' => AppMemberPointLog::CHANGE_TYPE_USE,
                'change_value' => -$points,
                'before_points' => $beforePoints,
                'after_points' => $pointAccount->available_points,
                'source_type' => AppMemberPointLog::SOURCE_TYPE_CONSUME,
                'order_no' => $orderNo,
                'title' => $title,
                'remark' => $remark,
                'client_ip' => $clientIp,
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => '积分消费成功',
                'data' => [
                    'used_points' => $points,
                    'available_points' => $pointAccount->available_points,
                ],
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('积分消费失败', [
                'member_id' => $memberId,
                'points' => $points,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }


    /**
     * 同步消费积分（不走队列，用于需要即时扣减的场景）
     *
     * @param int $memberId 用户ID
     * @param int $points 消费积分数
     * @param string $title 消费标题
     * @param string|null $orderNo 订单号
     * @param string|null $remark 备注
     * @param string|null $clientIp 客户端IP
     * @return array
     */
    public function consumeSync(
        int $memberId,
        int $points,
        string $title,
        ?string $orderNo = null,
        ?string $remark = null,
        ?string $clientIp = null
    ): array {
        return $this->processConsume($memberId, $points, $title, $orderNo, $remark, $clientIp);
    }

    /**
     * 检查积分是否足够
     *
     * @param int $memberId 用户ID
     * @param int $points 需要的积分数
     * @return bool
     */
    public function checkPointsEnough(int $memberId, int $points): bool
    {
        $pointAccount = AppMemberPoint::getOrCreate($memberId);
        return $pointAccount->hasEnoughPoints($points);
    }

    /**
     * 冻结积分
     *
     * @param int $memberId 用户ID
     * @param int $points 冻结积分数
     * @param string $title 冻结原因
     * @param string|null $orderNo 订单号
     * @param string|null $clientIp 客户端IP
     * @return array
     */
    public function freezePoints(
        int $memberId,
        int $points,
        string $title,
        ?string $orderNo = null,
        ?string $clientIp = null
    ): array {
        if ($points <= 0) {
            return ['success' => false, 'message' => '冻结积分数必须大于0'];
        }

        $pointAccount = AppMemberPoint::getOrCreate($memberId);

        if (!$pointAccount->hasEnoughPoints($points)) {
            return ['success' => false, 'message' => '可用积分不足'];
        }

        DB::beginTransaction();
        try {
            $beforePoints = $pointAccount->available_points;

            $pointAccount->freezePoints($points);

            $this->createPointLog([
                'member_id' => $memberId,
                'change_type' => AppMemberPointLog::CHANGE_TYPE_FREEZE,
                'change_value' => -$points,
                'before_points' => $beforePoints,
                'after_points' => $pointAccount->available_points,
                'source_type' => AppMemberPointLog::SOURCE_TYPE_CONSUME,
                'order_no' => $orderNo,
                'title' => $title,
                'remark' => '积分冻结',
                'client_ip' => $clientIp,
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => '积分冻结成功',
                'data' => [
                    'frozen_points' => $points,
                    'available_points' => $pointAccount->available_points,
                ],
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('积分冻结失败', [
                'member_id' => $memberId,
                'points' => $points,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * 解冻积分
     *
     * @param int $memberId 用户ID
     * @param int $points 解冻积分数
     * @param string $title 解冻原因
     * @param bool $toAvailable 是否返还到可用积分
     * @param string|null $orderNo 订单号
     * @param string|null $clientIp 客户端IP
     * @return array
     */
    public function unfreezePoints(
        int $memberId,
        int $points,
        string $title,
        bool $toAvailable = true,
        ?string $orderNo = null,
        ?string $clientIp = null
    ): array {
        if ($points <= 0) {
            return ['success' => false, 'message' => '解冻积分数必须大于0'];
        }

        $pointAccount = AppMemberPoint::getOrCreate($memberId);

        if ($pointAccount->frozen_points < $points) {
            return ['success' => false, 'message' => '冻结积分不足'];
        }

        DB::beginTransaction();
        try {
            $beforePoints = $pointAccount->available_points;

            $pointAccount->unfreezePoints($points, $toAvailable);

            $changeType = $toAvailable ? AppMemberPointLog::CHANGE_TYPE_UNFREEZE : AppMemberPointLog::CHANGE_TYPE_USE;
            $changeValue = $toAvailable ? $points : -$points;

            $this->createPointLog([
                'member_id' => $memberId,
                'change_type' => $changeType,
                'change_value' => $changeValue,
                'before_points' => $beforePoints,
                'after_points' => $pointAccount->available_points,
                'source_type' => $toAvailable ? AppMemberPointLog::SOURCE_TYPE_REFUND : AppMemberPointLog::SOURCE_TYPE_CONSUME,
                'order_no' => $orderNo,
                'title' => $title,
                'remark' => $toAvailable ? '积分解冻返还' : '冻结积分扣除',
                'client_ip' => $clientIp,
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => '积分解冻成功',
                'data' => [
                    'unfrozen_points' => $points,
                    'available_points' => $pointAccount->available_points,
                ],
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('积分解冻失败', [
                'member_id' => $memberId,
                'points' => $points,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }


    /**
     * 后台赠送积分
     *
     * @param int $memberId 用户ID
     * @param int $points 赠送积分数
     * @param string $title 赠送原因
     * @param int|null $operatorId 操作人ID
     * @param string|null $operatorName 操作人名称
     * @param string|null $remark 备注
     * @return array
     */
    public function giftPoints(
        int $memberId,
        int $points,
        string $title,
        ?int $operatorId = null,
        ?string $operatorName = null,
        ?string $remark = null
    ): array {
        if ($points <= 0) {
            return ['success' => false, 'message' => '赠送积分数必须大于0'];
        }

        DB::beginTransaction();
        try {
            $pointAccount = AppMemberPoint::getOrCreate($memberId);
            $beforePoints = $pointAccount->available_points;

            $pointAccount->addPoints($points);

            $this->createPointLog([
                'member_id' => $memberId,
                'change_type' => AppMemberPointLog::CHANGE_TYPE_EARN,
                'change_value' => $points,
                'before_points' => $beforePoints,
                'after_points' => $pointAccount->available_points,
                'source_type' => AppMemberPointLog::SOURCE_TYPE_GIFT,
                'title' => $title,
                'remark' => $remark,
                'operator_id' => $operatorId,
                'operator_name' => $operatorName,
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => '积分赠送成功',
                'data' => [
                    'gift_points' => $points,
                    'available_points' => $pointAccount->available_points,
                ],
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('积分赠送失败', [
                'member_id' => $memberId,
                'points' => $points,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * 后台扣除积分
     *
     * @param int $memberId 用户ID
     * @param int $points 扣除积分数
     * @param string $title 扣除原因
     * @param int|null $operatorId 操作人ID
     * @param string|null $operatorName 操作人名称
     * @param string|null $remark 备注
     * @return array
     */
    public function deductPoints(
        int $memberId,
        int $points,
        string $title,
        ?int $operatorId = null,
        ?string $operatorName = null,
        ?string $remark = null
    ): array {
        if ($points <= 0) {
            return ['success' => false, 'message' => '扣除积分数必须大于0'];
        }

        $pointAccount = AppMemberPoint::getOrCreate($memberId);

        if (!$pointAccount->hasEnoughPoints($points)) {
            return ['success' => false, 'message' => '可用积分不足'];
        }

        DB::beginTransaction();
        try {
            $beforePoints = $pointAccount->available_points;

            $pointAccount->usePoints($points);

            $this->createPointLog([
                'member_id' => $memberId,
                'change_type' => AppMemberPointLog::CHANGE_TYPE_ADJUST,
                'change_value' => -$points,
                'before_points' => $beforePoints,
                'after_points' => $pointAccount->available_points,
                'source_type' => AppMemberPointLog::SOURCE_TYPE_DEDUCT,
                'title' => $title,
                'remark' => $remark,
                'operator_id' => $operatorId,
                'operator_name' => $operatorName,
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => '积分扣除成功',
                'data' => [
                    'deducted_points' => $points,
                    'available_points' => $pointAccount->available_points,
                ],
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('积分扣除失败', [
                'member_id' => $memberId,
                'points' => $points,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }


    /**
     * 获取用户积分账户信息
     *
     * @param int $memberId 用户ID
     * @return array
     */
    public function getPointAccount(int $memberId): array
    {
        $pointAccount = AppMemberPoint::getOrCreate($memberId);

        return [
            'total_points' => $pointAccount->total_points,
            'used_points' => $pointAccount->used_points,
            'available_points' => $pointAccount->available_points,
            'frozen_points' => $pointAccount->frozen_points,
            'expired_points' => $pointAccount->expired_points,
            'level_points' => $pointAccount->level_points,
        ];
    }

    /**
     * 获取用户积分流水列表
     *
     * @param int $memberId 用户ID
     * @param int|null $changeType 变动类型
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @return array
     */
    public function getPointLogs(int $memberId, ?int $changeType = null, int $page = 1, int $pageSize = 20): array
    {
        $query = AppMemberPointLog::byMember($memberId)
            ->select([
                'log_id',
                'change_type',
                'change_value',
                'before_points',
                'after_points',
                'source_type',
                'task_code',
                'title',
                'remark',
                'created_at',
            ])
            ->orderBy('created_at', 'desc');

        if ($changeType !== null) {
            $query->byChangeType($changeType);
        }

        $total = $query->count();
        $logs = $query->skip(($page - 1) * $pageSize)
            ->take($pageSize)
            ->get();

        return [
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
            'list' => $logs->map(function ($log) {
                return [
                    'log_id' => $log->log_id,
                    'change_type' => $log->change_type,
                    'change_type_text' => $log->change_type_text,
                    'change_value' => $log->change_value,
                    'before_points' => $log->before_points,
                    'after_points' => $log->after_points,
                    'source_type' => $log->source_type,
                    'source_type_text' => $log->source_type_text,
                    'task_code' => $log->task_code,
                    'title' => $log->title,
                    'remark' => $log->remark,
                    'created_at' => $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : null,
                ];
            })->toArray(),
        ];
    }

    /**
     * 获取任务列表（含用户完成状态）
     *
     * @param int $memberId 用户ID
     * @return array
     */
    public function getTaskList(int $memberId): array
    {
        // 获取日常任务
        $dailyTasks = AppPointTask::getDailyTasks();
        $dailyTaskList = [];

        foreach ($dailyTasks as $task) {
            $todayCount = AppMemberTaskRecord::getTodayCount($memberId, $task->task_code);
            $dailyTaskList[] = [
                'task_id' => $task->task_id,
                'task_code' => $task->task_code,
                'task_name' => $task->task_name,
                'point_value' => $task->point_value,
                'daily_limit' => $task->daily_limit,
                'today_count' => $todayCount,
                'is_completed' => $task->daily_limit > 0 && $todayCount >= $task->daily_limit,
                'description' => $task->description,
                'icon' => $task->icon,
                'jump_url' => $task->jump_url,
            ];
        }

        // 获取成长任务
        $growthTasks = AppPointTask::getGrowthTasks();
        $memberGrowthTasks = AppMemberGrowthTask::getMemberGrowthTasks($memberId);
        $growthTaskList = [];

        foreach ($growthTasks as $task) {
            $memberTask = isset($memberGrowthTasks[$task->task_code]) ? $memberGrowthTasks[$task->task_code] : null;
            $isCompleted = $memberTask ? $memberTask->is_completed === AppMemberGrowthTask::COMPLETED : false;

            $growthTaskList[] = [
                'task_id' => $task->task_id,
                'task_code' => $task->task_code,
                'task_name' => $task->task_name,
                'point_value' => $task->point_value,
                'is_completed' => $isCompleted,
                'complete_time' => $memberTask && $memberTask->complete_time
                    ? $memberTask->complete_time->format('Y-m-d H:i:s')
                    : null,
                'description' => $task->description,
                'icon' => $task->icon,
                'jump_url' => $task->jump_url,
            ];
        }

        return [
            'daily_tasks' => $dailyTaskList,
            'growth_tasks' => $growthTaskList,
        ];
    }


    /**
     * 获取用户今日积分获取统计
     *
     * @param int $memberId 用户ID
     * @return array
     */
    public function getTodayEarnStats(int $memberId): array
    {
        $today = date('Y-m-d');

        $totalEarned = AppMemberPointLog::byMember($memberId)
            ->earned()
            ->whereDate('created_at', $today)
            ->sum('change_value');

        $taskRecords = AppMemberTaskRecord::byMember($memberId)
            ->byDate($today)
            ->get();

        $taskStats = [];
        foreach ($taskRecords as $record) {
            if (!isset($taskStats[$record->task_code])) {
                $taskStats[$record->task_code] = [
                    'task_code' => $record->task_code,
                    'count' => 0,
                    'total_points' => 0,
                ];
            }
            $taskStats[$record->task_code]['count']++;
            $taskStats[$record->task_code]['total_points'] += $record->point_value;
        }

        return [
            'date' => $today,
            'total_earned' => (int)$totalEarned,
            'task_stats' => array_values($taskStats),
        ];
    }

    /**
     * 检查日常任务是否可完成
     *
     * @param int $memberId 用户ID
     * @param string $taskCode 任务编码
     * @param string|null $bizId 业务ID
     * @return array
     */
    public function canCompleteTask(int $memberId, string $taskCode, ?string $bizId = null): array
    {
        $task = AppPointTask::getByCode($taskCode);
        if (!$task) {
            return ['can_complete' => false, 'reason' => '任务不存在或已禁用'];
        }

        if ($task->isGrowthTask()) {
            if (AppMemberGrowthTask::isCompleted($memberId, $taskCode)) {
                return ['can_complete' => false, 'reason' => '该成长任务已完成'];
            }
            return ['can_complete' => true, 'reason' => ''];
        }

        // 日常任务检查
        $todayCount = AppMemberTaskRecord::getTodayCount($memberId, $taskCode);
        if ($task->daily_limit > 0 && $todayCount >= $task->daily_limit) {
            return ['can_complete' => false, 'reason' => '今日任务次数已达上限'];
        }

        if ($bizId && AppMemberTaskRecord::hasCompletedBiz($memberId, $taskCode, $bizId)) {
            return ['can_complete' => false, 'reason' => '该任务已完成'];
        }

        if ($task->total_limit > 0) {
            $totalCount = AppMemberTaskRecord::getTotalCount($memberId, $taskCode);
            if ($totalCount >= $task->total_limit) {
                return ['can_complete' => false, 'reason' => '任务总次数已达上限'];
            }
        }

        return ['can_complete' => true, 'reason' => ''];
    }

    /**
     * 创建积分日志
     *
     * @param array $data
     * @return AppMemberPointLog
     */
    protected function createPointLog(array $data): AppMemberPointLog
    {
        return AppMemberPointLog::createLog($data);
    }
}
