<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppCheckinStat extends Model
{
    use HasFactory;

    protected $table = 'app_checkin_stat';
    protected $primaryKey = 'stat_id';
    public $timestamps = false;

    protected $fillable = [
        'member_id',
        'total_checkin_days',
        'continuous_days',
        'max_continuous_days',
        'total_reward_value',
        'last_checkin_date',
        'last_checkin_time',
        'create_time',
        'update_time',
    ];

    protected $casts = [
        'stat_id' => 'integer',
        'member_id' => 'integer',
        'total_checkin_days' => 'integer',
        'continuous_days' => 'integer',
        'max_continuous_days' => 'integer',
        'total_reward_value' => 'integer',
        'last_checkin_date' => 'date',
        'last_checkin_time' => 'datetime',
        'create_time' => 'datetime',
        'update_time' => 'datetime',
    ];

    /**
     * 关联用户
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function member()
    {
        return $this->belongsTo(AppMemberBase::class, 'member_id', 'member_id');
    }

    /**
     * 获取或创建用户签到统计
     *
     * @param int $memberId
     * @return self
     */
    public static function getOrCreate(int $memberId): self
    {
        $stat = self::where('member_id', $memberId)->first();

        if (!$stat) {
            $stat = self::create([
                'member_id' => $memberId,
                'total_checkin_days' => 0,
                'continuous_days' => 0,
                'max_continuous_days' => 0,
                'total_reward_value' => 0,
                'create_time' => now(),
                'update_time' => now(),
            ]);
        }

        return $stat;
    }

    /**
     * 检查用户今天是否已签到
     *
     * @return bool
     */
    public function hasCheckedInToday(): bool
    {
        if (!$this->last_checkin_date) {
            return false;
        }

        return $this->last_checkin_date->format('Y-m-d') === date('Y-m-d');
    }

    /**
     * 计算新的连续签到天数
     *
     * @return int
     */
    public function calculateContinuousDays(): int
    {
        if (!$this->last_checkin_date) {
            return 1;
        }

        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $lastDate = $this->last_checkin_date->format('Y-m-d');

        // 昨天签到过，连续天数+1
        if ($lastDate === $yesterday) {
            return $this->continuous_days + 1;
        }

        // 今天已签到，返回当前连续天数
        if ($lastDate === date('Y-m-d')) {
            return $this->continuous_days;
        }

        // 断签，重置为1
        return 1;
    }

    /**
     * 更新签到统计
     *
     * @param int $continuousDays 连续签到天数
     * @param int $rewardValue 本次获得奖励
     * @return bool
     */
    public function updateCheckinStat(int $continuousDays, int $rewardValue): bool
    {
        $this->total_checkin_days += 1;
        $this->continuous_days = $continuousDays;
        $this->total_reward_value += $rewardValue;
        $this->last_checkin_date = date('Y-m-d');
        $this->last_checkin_time = now();
        $this->update_time = now();

        // 更新历史最大连续签到天数
        if ($continuousDays > $this->max_continuous_days) {
            $this->max_continuous_days = $continuousDays;
        }

        return $this->save();
    }
}
