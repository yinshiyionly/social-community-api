<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 用户任务完成记录表
 *
 * @property int $record_id
 * @property int $member_id
 * @property int $task_id
 * @property string $task_code
 * @property int $task_type
 * @property int $point_value
 * @property string $complete_date
 * @property int $complete_count
 * @property string|null $biz_id
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class AppMemberTaskRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'app_member_task_record';
    protected $primaryKey = 'record_id';

    protected $fillable = [
        'member_id',
        'task_id',
        'task_code',
        'task_type',
        'point_value',
        'complete_date',
        'complete_count',
        'biz_id',
    ];

    protected $casts = [
        'record_id' => 'integer',
        'member_id' => 'integer',
        'task_id' => 'integer',
        'task_type' => 'integer',
        'point_value' => 'integer',
        'complete_count' => 'integer',
        'complete_date' => 'date',
    ];

    /**
     * 关联用户
     */
    public function member()
    {
        return $this->belongsTo(AppMemberBase::class, 'member_id', 'member_id');
    }

    /**
     * 关联任务
     */
    public function task()
    {
        return $this->belongsTo(AppPointTask::class, 'task_id', 'task_id');
    }

    /**
     * 查询作用域：按用户筛选
     */
    public function scopeByMember($query, int $memberId)
    {
        return $query->where('member_id', $memberId);
    }

    /**
     * 查询作用域：按任务编码筛选
     */
    public function scopeByTaskCode($query, string $taskCode)
    {
        return $query->where('task_code', $taskCode);
    }

    /**
     * 查询作用域：按日期筛选
     */
    public function scopeByDate($query, string $date)
    {
        return $query->where('complete_date', $date);
    }

    /**
     * 查询作用域：今日记录
     */
    public function scopeToday($query)
    {
        return $query->where('complete_date', date('Y-m-d'));
    }

    /**
     * 获取用户今日某任务完成次数
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
     */
    public static function getTotalCount(int $memberId, string $taskCode): int
    {
        return self::byMember($memberId)
            ->byTaskCode($taskCode)
            ->count();
    }

    /**
     * 检查用户今日某任务是否已完成指定业务
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
