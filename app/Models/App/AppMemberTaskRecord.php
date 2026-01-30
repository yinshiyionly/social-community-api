<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppMemberTaskRecord extends Model
{
    use HasFactory;

    protected $table = 'app_member_task_record';
    protected $primaryKey = 'record_id';
    public $timestamps = false;

    protected $fillable = [
        'member_id',
        'task_id',
        'task_code',
        'task_type',
        'point_value',
        'complete_date',
        'complete_count',
        'biz_id',
        'create_time',
    ];

    protected $casts = [
        'record_id' => 'integer',
        'member_id' => 'integer',
        'task_id' => 'integer',
        'task_type' => 'integer',
        'point_value' => 'integer',
        'complete_count' => 'integer',
        'complete_date' => 'date',
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
     * 关联任务
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function task()
    {
        return $this->belongsTo(AppPointTask::class, 'task_id', 'task_id');
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
     * 查询作用域：按任务编码筛选
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $taskCode
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByTaskCode($query, string $taskCode)
    {
        return $query->where('task_code', $taskCode);
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
        return $query->where('complete_date', $date);
    }

    /**
     * 查询作用域：今日记录
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeToday($query)
    {
        return $query->where('complete_date', date('Y-m-d'));
    }

    /**
     * 获取用户今日某任务完成次数
     *
     * @param int $memberId
     * @param string $taskCode
     * @return int
     */
    public static function getTodayCount(int $memberId, string $taskCode): int
    {
        return self::byMember($memberId)
            ->byTaskCode($taskCode)
            ->today()
            ->count();
    }

    /**
     * 获取用户某任务总完成次数
     *
     * @param int $memberId
     * @param string $taskCode
     * @return int
     */
    public static function getTotalCount(int $memberId, string $taskCode): int
    {
        return self::byMember($memberId)
            ->byTaskCode($taskCode)
            ->count();
    }

    /**
     * 检查用户今日某任务是否已完成指定业务
     *
     * @param int $memberId
     * @param string $taskCode
     * @param string $bizId
     * @return bool
     */
    public static function hasCompletedBiz(int $memberId, string $taskCode, string $bizId): bool
    {
        return self::byMember($memberId)
            ->byTaskCode($taskCode)
            ->today()
            ->where('biz_id', $bizId)
            ->exists();
    }
}
