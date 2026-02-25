<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 用户积分任务配置表
 *
 * @property int $task_id
 * @property string $task_code
 * @property string $task_name
 * @property int $task_type
 * @property string|null $task_category
 * @property int $point_value
 * @property int $daily_limit
 * @property int $total_limit
 * @property string|null $icon
 * @property string|null $description
 * @property string|null $jump_url
 * @property int $sort_order
 * @property int $status
 * @property \Carbon\Carbon|null $start_time
 * @property \Carbon\Carbon|null $end_time
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class AppPointTask extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'app_member_point_task';
    protected $primaryKey = 'task_id';

    // 任务类型
    const TYPE_DAILY = 1;      // 日常任务
    const TYPE_GROWTH = 2;     // 成长任务（一次性）
    const TYPE_SPECIAL = 3;    // 特殊任务

    // 任务分类
    const CATEGORY_DAILY = 'daily';       // 日常
    const CATEGORY_GROWTH = 'growth';     // 成长
    const CATEGORY_ACTIVITY = 'activity'; // 活动

    // 状态
    const STATUS_ENABLED = 1;   // 启用
    const STATUS_DISABLED = 2;  // 禁用

    protected $fillable = [
        'task_code',
        'task_name',
        'task_type',
        'task_category',
        'point_value',
        'daily_limit',
        'total_limit',
        'icon',
        'description',
        'jump_url',
        'sort_order',
        'status',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'task_id' => 'integer',
        'task_type' => 'integer',
        'point_value' => 'integer',
        'daily_limit' => 'integer',
        'total_limit' => 'integer',
        'sort_order' => 'integer',
        'status' => 'integer',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    /**
     * 查询作用域：启用状态
     */
    public function scopeEnabled($query)
    {
        return $query->where('status', self::STATUS_ENABLED);
    }

    /**
     * 查询作用域：按任务类型
     */
    public function scopeByType($query, int $type)
    {
        return $query->where('task_type', $type);
    }

    /**
     * 查询作用域：按任务分类
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('task_category', $category);
    }

    /**
     * 查询作用域：生效中
     */
    public function scopeActive($query)
    {
        $now = now();
        return $query->where(function ($q) use ($now) {
            $q->whereNull('start_time')
              ->orWhere('start_time', '<=', $now);
        })->where(function ($q) use ($now) {
            $q->whereNull('end_time')
              ->orWhere('end_time', '>=', $now);
        });
    }

    /**
     * 根据任务编码获取任务配置
     */
    public static function getByCode(string $taskCode)
    {
        return self::enabled()
            ->active()
            ->where('task_code', $taskCode)
            ->first();
    }

    /**
     * 获取日常任务列表
     */
    public static function getDailyTasks()
    {
        return self::enabled()
            ->active()
            ->byType(self::TYPE_DAILY)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * 获取成长任务列表
     */
    public static function getGrowthTasks()
    {
        return self::enabled()
            ->active()
            ->byType(self::TYPE_GROWTH)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * 判断是否为日常任务
     */
    public function isDailyTask(): bool
    {
        return $this->task_type === self::TYPE_DAILY;
    }

    /**
     * 判断是否为成长任务
     */
    public function isGrowthTask(): bool
    {
        return $this->task_type === self::TYPE_GROWTH;
    }
}
