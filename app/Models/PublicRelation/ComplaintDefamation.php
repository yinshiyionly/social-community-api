<?php

declare(strict_types=1);

namespace App\Models\PublicRelation;

use App\Models\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 诽谤类举报模型 - 公关维权-我要投诉-诽谤类
 *
 * @property int $id
 * @property string $report_type
 * @property string $site_name
 * @property array|null $site_url
 * @property int $material_id
 * @property string $human_name
 * @property array|null $report_material
 * @property string|null $report_content
 * @property string $send_email
 * @property int $email_config_id
 * @property string $channel_name
 * @property int $report_state
 * @property \Illuminate\Support\Carbon|null $report_time
 * @property \Illuminate\Support\Carbon|null $completion_time
 * @property int $status
 * @property string|null $create_by
 * @property string|null $update_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class ComplaintDefamation extends Model
{
    use SoftDeletes;
    use HasAuditFields;

    // ==================== 状态常量 ====================

    /**
     * Status: Enabled
     */
    public const STATUS_ENABLED = 1;

    /**
     * Status: Disabled
     */
    public const STATUS_DISABLED = 2;

    /**
     * Status labels
     */
    public const STATUS_LABELS = [
        self::STATUS_ENABLED => '启用',
        self::STATUS_DISABLED => '禁用',
    ];

    // ==================== 举报状态常量 ====================

    /**
     * Report State: 平台审核中
     */
    public const REPORT_STATE_PLATFORM_REVIEWING = 1;

    /**
     * Report State: 平台驳回
     */
    public const REPORT_STATE_PLATFORM_REJECTED = 2;

    /**
     * Report State: 平台审核通过
     */
    public const REPORT_STATE_PLATFORM_APPROVED = 3;

    /**
     * Report State: 官方审核中
     */
    public const REPORT_STATE_OFFICIAL_REVIEWING = 4;

    /**
     * Report state labels
     */
    public const REPORT_STATE_LABELS = [
        self::REPORT_STATE_PLATFORM_REVIEWING => '平台审核中',
        self::REPORT_STATE_PLATFORM_REJECTED => '平台驳回',
        self::REPORT_STATE_PLATFORM_APPROVED => '平台审核通过',
        self::REPORT_STATE_OFFICIAL_REVIEWING => '官方审核中',
    ];

    // ==================== 举报类型常量 ====================

    /**
     * Report Type: 诽谤类
     */
    public const REPORT_TYPE_DEFAMATION = '诽谤类';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'complaint_defamation';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'report_type',
        'site_name',
        'site_url',
        'material_id',
        'human_name',
        'report_material',
        'report_content',
        'send_email',
        'email_config_id',
        'channel_name',
        'report_state',
        'report_time',
        'completion_time',
        'status',
        'create_by',
        'update_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'site_url' => 'array',
        'report_material' => 'array',
        'material_id' => 'integer',
        'report_state' => 'integer',
        'email_config_id' => 'integer',
        'status' => 'integer',
        'report_time' => 'datetime',
        'completion_time' => 'datetime',
    ];

    /**
     * Get the status label.
     *
     * @return string
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? '未知';
    }

    /**
     * Get the report state label.
     *
     * @return string
     */
    public function getReportStateLabelAttribute(): string
    {
        return self::REPORT_STATE_LABELS[$this->report_state] ?? '未知';
    }
}
