<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 用户成长任务状态表
 *
 * @property int $id
 * @property int $member_id
 * @property int $task_id
 * @property string $task_code
 * @property int $is_completed
 * @property \Carbon\Carbon|null $complete_time
 * @property int $point_value
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class AppMemberGrowthTask extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'app_member_growth_task';
    protected $primaryKey = 'id';

    // 完成状态
    const NOT_COMPLETED = 0;
    const COMPLETED = 1;

    protected $fillable = [
        'member_id',
        'task_id',
        'task_code',
        'is_completed',
        'complete_time',
        'point_value',
    ];

    protected $casts = [
        'id' => 'integer',
        'member_id' => 'integer',
        'task_id' => 'integer',
        'is_completed' => 'integer',
        'point_value' => 'integer',
        'complete_time' => 'datetime',
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
     * 查询作用域：已完成
     */
    public function scopeCompleted($query)
    {
        return $query->where('is_completed', self::COMPLETED);
    }

    /**
     * 查询作用域：未完成
     */
    public function scopeNotCompleted($query)
    {
        return $query->where('is_completed', self::NOT_COMPLETED);
    }

    /**
     * 检查用户是否已完成某成长任务
     */
    public static function isCompleted(int $memberId, string $taskCode): bool
    {
        return self::byMember($memberId)
            ->byTaskCode($taskCode)
            ->completed()
            ->exists();
    }

    /**
     * 获取或创建用户成长任务状态
     */
    public static function getOrCreate(int $memberId, AppPointTask $task): self
    {
        $record = self::byMember($memberId)
            ->byTaskCode($task->task_code)
            ->first();

        if (!$record) {
            $record = self::create([
                'member_id' => $memberId,
                'task_id' => $task->task_id,
                'task_code' => $task->task_code,
                'is_completed' => self::NOT_COMPLETED,
                'point_value' => 0,
            ]);
        }

        return $record;
    }

    /**
     * 标记任务完成
     */
    public function markCompleted(int $pointValue): bool
    {
        $this->is_completed = self::COMPLETED;
        $this->complete_time = now();
        $this->point_value = $pointValue;

        return $this->save();
    }

    /**
     * 获取用户所有成长任务状态
     */
    public static function getMemberGrowthTasks(int $memberId)
    {
        return self::byMember($memberId)->get()->keyBy('task_code');
    }
}
