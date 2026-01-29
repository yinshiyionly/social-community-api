<?php

namespace App\Services\App;

use App\Models\App\AppCheckinConfig;
use App\Models\App\AppCheckinRecord;
use App\Models\App\AppCheckinStat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckinService
{
    /**
     * 执行签到
     *
     * @param int $memberId 用户ID
     * @param string|null $clientIp 客户端IP
     * @param string|null $deviceInfo 设备信息
     * @return array
     * @throws \Exception
     */
    public function checkin(int $memberId, ?string $clientIp = null, ?string $deviceInfo = null): array
    {
        // 获取或创建用户签到统计
        $stat = AppCheckinStat::getOrCreate($memberId);

        // 检查今天是否已签到
        if ($stat->hasCheckedInToday()) {
            return [
                'success' => false,
                'message' => '今天已签到',
                'data' => $this->buildCheckinResult($stat, null),
            ];
        }

        // 计算连续签到天数
        $continuousDays = $stat->calculateContinuousDays();

        // 获取奖励配置
        $config = AppCheckinConfig::getRewardConfig($continuousDays);
        $rewardValue = $config ? $config->reward_value : 0;
        $extraRewardValue = $config ? $config->extra_reward_value : 0;
        $rewardType = $config ? $config->reward_type : AppCheckinConfig::REWARD_TYPE_POINTS;

        $totalReward = $rewardValue + $extraRewardValue;

        DB::beginTransaction();
        try {
            // 创建签到记录
            $record = AppCheckinRecord::create([
                'member_id' => $memberId,
                'checkin_date' => date('Y-m-d'),
                'continuous_days' => $continuousDays,
                'reward_type' => $rewardType,
                'reward_value' => $rewardValue,
                'extra_reward_value' => $extraRewardValue,
                'checkin_time' => now(),
                'client_ip' => $clientIp,
                'device_info' => $deviceInfo,
                'create_time' => now(),
            ]);

            // 更新签到统计
            $stat->updateCheckinStat($continuousDays, $totalReward);

            // TODO: 发放奖励（积分/经验等），可在此处调用积分服务
            // $this->pointService->addPoints($memberId, $totalReward, '签到奖励');

            DB::commit();

            Log::channel('job')->info('用户签到成功', [
                'member_id' => $memberId,
                'continuous_days' => $continuousDays,
                'reward_value' => $rewardValue,
                'extra_reward_value' => $extraRewardValue,
            ]);

            return [
                'success' => true,
                'message' => '签到成功',
                'data' => $this->buildCheckinResult($stat, $record, $continuousDays, $totalReward),
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('用户签到失败', [
                'member_id' => $memberId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * 获取签到状态
     *
     * @param int $memberId 用户ID
     * @return array
     */
    public function getCheckinStatus(int $memberId): array
    {
        $stat = AppCheckinStat::getOrCreate($memberId);
        $hasCheckedIn = $stat->hasCheckedInToday();

        // 计算今天签到可获得的奖励
        $nextContinuousDays = $hasCheckedIn ? $stat->continuous_days : $stat->calculateContinuousDays();
        $config = AppCheckinConfig::getRewardConfig($nextContinuousDays);

        return [
            'has_checked_in' => $hasCheckedIn,
            'continuous_days' => $stat->continuous_days,
            'total_checkin_days' => $stat->total_checkin_days,
            'max_continuous_days' => $stat->max_continuous_days,
            'total_reward_value' => $stat->total_reward_value,
            'last_checkin_date' => $stat->last_checkin_date ? $stat->last_checkin_date->format('Y-m-d') : null,
            'today_reward' => $config ? ($config->reward_value + $config->extra_reward_value) : 0,
        ];
    }

    /**
     * 获取签到奖励配置列表
     *
     * @param int $memberId 用户ID
     * @return array
     */
    public function getRewardConfigList(int $memberId): array
    {
        $stat = AppCheckinStat::getOrCreate($memberId);
        $configs = AppCheckinConfig::getRewardList();

        // 计算当前周期内的签到天数（1-7循环）
        $currentDayInCycle = $stat->continuous_days > 0 ? (($stat->continuous_days - 1) % 7) + 1 : 0;
        $hasCheckedInToday = $stat->hasCheckedInToday();

        $list = [];
        foreach ($configs as $config) {
            $isCompleted = false;

            if ($hasCheckedInToday) {
                // 今天已签到，当天及之前的都算完成
                $isCompleted = $config->day_num <= $currentDayInCycle;
            } else {
                // 今天未签到，只有之前的算完成
                $isCompleted = $config->day_num < $currentDayInCycle;
            }

            $list[] = [
                'day_num' => $config->day_num,
                'reward_value' => $config->reward_value,
                'extra_reward_value' => $config->extra_reward_value,
                'total_reward' => $config->reward_value + $config->extra_reward_value,
                'is_completed' => $isCompleted,
                'is_today' => !$hasCheckedInToday && $config->day_num === ($currentDayInCycle + 1),
            ];
        }

        return $list;
    }

    /**
     * 获取用户某月签到记录
     *
     * @param int $memberId 用户ID
     * @param int $year 年份
     * @param int $month 月份
     * @return array
     */
    public function getMonthlyRecords(int $memberId, int $year, int $month): array
    {
        $records = AppCheckinRecord::getMonthlyRecords($memberId, $year, $month);

        $checkinDates = [];
        foreach ($records as $record) {
            $checkinDates[] = [
                'date' => $record->checkin_date->format('Y-m-d'),
                'day' => (int)$record->checkin_date->format('d'),
                'continuous_days' => $record->continuous_days,
                'reward_value' => $record->reward_value + $record->extra_reward_value,
            ];
        }

        return [
            'year' => $year,
            'month' => $month,
            'checkin_count' => count($checkinDates),
            'checkin_dates' => $checkinDates,
        ];
    }

    /**
     * 构建签到结果数据
     *
     * @param AppCheckinStat $stat
     * @param AppCheckinRecord|null $record
     * @param int|null $continuousDays
     * @param int|null $totalReward
     * @return array
     */
    private function buildCheckinResult(
        AppCheckinStat $stat,
        ?AppCheckinRecord $record,
        ?int $continuousDays = null,
        ?int $totalReward = null
    ): array {
        return [
            'continuous_days' => $continuousDays ?? $stat->continuous_days,
            'total_checkin_days' => $stat->total_checkin_days,
            'reward_value' => $totalReward ?? 0,
            'max_continuous_days' => $stat->max_continuous_days,
            'total_reward_value' => $stat->total_reward_value,
        ];
    }
}
