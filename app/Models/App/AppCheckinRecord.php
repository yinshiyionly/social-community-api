<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppCheckinRecord extends Model
{
    use HasFactory;

    protected $table = 'app_checkin_record';
    protected $primaryKey = 'record_id';
    public $timestamps = false;

    protected $fillable = [
        'member_id',
        'checkin_date',
        'continuous_days',
        'reward_type',
        'reward_value',
        'extra_reward_value',
        'checkin_time',
        'client_ip',
        'device_info',
        'create_time',
    ];

    protected $casts = [
        'record_id' => 'integer',
        'member_id' => 'integer',
        'continuous_days' => 'integer',
        'reward_type' => 'integer',
        'reward_value' => 'integer',
        'extra_reward_value' => 'integer',
        'checkin_date' => 'date',
        'checkin_time' => 'datetime',
        'create_time' => 'datetime',
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
     * 查询作用域：按用户筛选
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $memberId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByMember($query, int $memberId)
    {
        return $query->where('member_id', $memberId);
    }

    /**
     * 查询作用域：按日期筛选
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $date
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByDate($query, string $date)
    {
        return $query->where('checkin_date', $date);
    }

    /**
     * 查询作用域：按月份筛选
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $year
     * @param int $month
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByMonth($query, int $year, int $month)
    {
        return $query->whereYear('checkin_date', $year)
                     ->whereMonth('checkin_date', $month);
    }

    /**
     * 检查用户今天是否已签到
     *
     * @param int $memberId
     * @return bool
     */
    public static function hasCheckedInToday(int $memberId): bool
    {
        return self::byMember($memberId)
            ->byDate(date('Y-m-d'))
            ->exists();
    }

    /**
     * 获取用户某月的签到记录
     *
     * @param int $memberId
     * @param int $year
     * @param int $month
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getMonthlyRecords(int $memberId, int $year, int $month)
    {
        return self::byMember($memberId)
            ->byMonth($year, $month)
            ->orderBy('checkin_date')
            ->get();
    }
}
