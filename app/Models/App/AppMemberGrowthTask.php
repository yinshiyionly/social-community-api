<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppMemberGrowthTask extends Model
{
    use HasFactory;

    protected $table = 'app_member_growth_task';
    protected $primaryKey = 'id';
    public $timestamps = false;

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
        'create_time',
        'update_time',
    ];

    protected $casts = [
        'id' => 'integer',
        'member_id' => 'integer',
        'task_id' => 'integer',
        'is_completed' => 'integer',
        'point_value' => 'integer',
        'complete_time' => 'datetime',
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
     * 查询作用域：已完成
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompleted($query)
    {
        return $query->where('is_completed', self::COMPLETED);
    }

    /**
     * 查询作用域：未完成
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotCompleted($query)
    {
        return $query->where('is_completed', self::NOT_COMPLETED);
    }

    /**
     * 检查用户是否已完成某成长任务
     *
     * @param int $memberId
     * @param string $taskCode
     * @return bool
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
     *
     * @param int $memberId
     * @param AppPointTask $task
     * @return self
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
                'create_time' => now(),
                'update_time' => now(),
            ]);
        }

        return $record;
    }

    /**
     * 标记任务完成
     *
     * @param int $pointValue 获得积分
     * @return bool
     */
    public function markCompleted(int $pointValue): bool
    {
        $this->is_completed = self::COMPLETED;
        $this->complete_time = now();
        $this->point_value = $pointValue;
        $this->update_time = now();

        return $this->save();
    }

    /**
     * 获取用户所有成长任务状态
     *
     * @param int $memberId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getMemberGrowthTasks(int $memberId)
    {
        return self::byMember($memberId)->get()->keyBy('task_code');
    }
}
