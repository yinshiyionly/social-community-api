<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppCheckinConfig extends Model
{
    use HasFactory;

    protected $table = 'app_checkin_config';
    protected $primaryKey = 'config_id';
    public $timestamps = false;

    // 奖励类型
    const REWARD_TYPE_POINTS = 1;      // 积分
    const REWARD_TYPE_EXPERIENCE = 2;  // 经验值

    // 状态
    const STATUS_ENABLED = 1;   // 启用
    const STATUS_DISABLED = 2;  // 禁用

    // 删除标志
    const DEL_FLAG_NORMAL = 0;   // 正常
    const DEL_FLAG_DELETED = 1;  // 已删除

    protected $fillable = [
        'day_num',
        'reward_type',
        'reward_value',
        'extra_reward_value',
        'status',
        'remark',
        'create_by',
        'create_time',
        'update_by',
        'update_time',
        'del_flag',
    ];

    protected $casts = [
        'config_id' => 'integer',
        'day_num' => 'integer',
        'reward_type' => 'integer',
        'reward_value' => 'integer',
        'extra_reward_value' => 'integer',
        'status' => 'integer',
        'del_flag' => 'integer',
        'create_time' => 'datetime',
        'update_time' => 'datetime',
    ];

    /**
     * 查询作用域：启用状态
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEnabled($query)
    {
        return $query->where('status', self::STATUS_ENABLED)
                     ->where('del_flag', self::DEL_FLAG_NORMAL);
    }

    /**
     * 根据连续签到天数获取奖励配置
     *
     * @param int $continuousDays 连续签到天数
     * @param int $rewardType 奖励类型
     * @return self|null
     */
    public static function getRewardConfig(int $continuousDays, int $rewardType = self::REWARD_TYPE_POINTS)
    {
        // 7天循环，第8天等同于第1天
        $dayNum = (($continuousDays - 1) % 7) + 1;

        return self::enabled()
            ->where('day_num', $dayNum)
            ->where('reward_type', $rewardType)
            ->first();
    }

    /**
     * 获取所有启用的奖励配置列表
     *
     * @param int $rewardType 奖励类型
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getRewardList(int $rewardType = self::REWARD_TYPE_POINTS)
    {
        return self::enabled()
            ->where('reward_type', $rewardType)
            ->orderBy('day_num')
            ->get();
    }
}
