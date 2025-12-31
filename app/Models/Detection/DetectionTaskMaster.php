<?php

declare(strict_types=1);

namespace App\Models\Detection;

use App\Models\Traits\BelongsToCreator;
use App\Models\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 实时监测任务主表模型
 *
 * @property int $task_id 主键ID
 * @property int $external_task_id 火山内容洞察实时任务task_id
 * @property array|null $external_rule 火山内容洞察实时任务rule规则-只读字段
 * @property int $external_enable_status 火山内容洞察实时任务开启状态
 * @property string $external_sync_mode 火山内容洞察实时任务是否支持同步队列消费
 * @property string $task_name 任务名称
 * @property array|null $text_rule 火山内容洞察实时任务rule规则-文本类
 * @property string $text_plain 内部系统明文规则-文本类
 * @property array|null $tag_rule 火山内容洞察实时任务rule规则-tag类
 * @property array|null $tag_plain 内部系统明文规则-tag类
 * @property array|null $based_location_rule 火山内容洞察实时任务rule规则-相关位置信息类
 * @property array|null $based_location_plain 内部系统明文规则-相关位置信息类
 * @property array|null $data_site 数据站点-抖音
 * @property string $warn_name 预警名称
 * @property \Illuminate\Support\Carbon|null $warn_reception_start_time 预警接收开始时间
 * @property \Illuminate\Support\Carbon|null $warn_reception_end_time 预警接收结束时间
 * @property int $warn_publish_email_state 预警邮箱推送开关
 * @property array|null $warn_publish_email_config 预警邮箱推送配置
 * @property int $warn_publish_wx_state 预警微信推送开关
 * @property array|null $warn_publish_wx_config 预警微信推送配置
 * @property int $status 状态
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @property \Illuminate\Support\Carbon|null $deleted_at 删除时间
 * @property int $create_by 创建人ID
 * @property int $update_by 更新人ID
 */
class DetectionTaskMaster extends Model
{
    use SoftDeletes;
    use HasAuditFields;
    use BelongsToCreator;

    // ==================== 状态常量 ====================

    /**
     * 状态: 启用
     */
    public const STATUS_ENABLED = 1;

    /**
     * 状态: 禁用
     */
    public const STATUS_DISABLED = 2;

    /**
     * 状态标签映射
     */
    public const STATUS_LABELS = [
        self::STATUS_ENABLED => '启用',
        self::STATUS_DISABLED => '禁用',
    ];

    // ==================== 外部任务开启状态常量 ====================

    /**
     * 外部任务开启状态: 开启
     */
    public const EXTERNAL_ENABLE_STATUS_ON = 1;

    /**
     * 外部任务开启状态: 关闭
     */
    public const EXTERNAL_ENABLE_STATUS_OFF = 0;

    // ==================== 推送开关常量 ====================

    /**
     * 推送开关: 开启
     */
    public const PUBLISH_STATE_ON = 1;

    /**
     * 推送开关: 关闭
     */
    public const PUBLISH_STATE_OFF = 2;

    /**
     * 表名
     *
     * @var string
     */
    protected $table = 'detection_task_master';

    /**
     * 主键字段
     *
     * @var string
     */
    protected $primaryKey = 'task_id';

    /**
     * 可批量赋值的字段
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'external_task_id',
        'external_rule',
        'external_enable_status',
        'external_sync_mode',
        'task_name',
        'text_rule',
        'text_plain',
        'tag_rule',
        'tag_plain',
        'based_location_rule',
        'based_location_plain',
        'data_site',
        'warn_name',
        'warn_reception_start_time',
        'warn_reception_end_time',
        'warn_publish_email_state',
        'warn_publish_email_config',
        'warn_publish_wx_state',
        'warn_publish_wx_config',
        'status',
        'create_by',
        'update_by',
    ];

    /**
     * 字段类型转换
     *
     * @var array<string, string>
     */
    protected $casts = [
        'external_task_id' => 'integer',
        'external_rule' => 'array',
        'external_enable_status' => 'integer',
        'text_rule' => 'array',
        'tag_rule' => 'array',
        'tag_plain' => 'array',
        'based_location_rule' => 'array',
        'based_location_plain' => 'array',
        'data_site' => 'array',
        'warn_reception_start_time' => 'datetime',
        'warn_reception_end_time' => 'datetime',
        'warn_publish_email_state' => 'integer',
        'warn_publish_email_config' => 'array',
        'warn_publish_wx_state' => 'integer',
        'warn_publish_wx_config' => 'array',
        'status' => 'integer',
    ];

    /**
     * 获取状态标签
     *
     * @return string
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? '未知';
    }

    /**
     * 判断任务是否启用
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->status === self::STATUS_ENABLED;
    }

    /**
     * 判断外部任务是否开启
     *
     * @return bool
     */
    public function isExternalEnabled(): bool
    {
        return $this->external_enable_status === self::EXTERNAL_ENABLE_STATUS_ON;
    }
}
