<?php

declare(strict_types=1);

namespace App\Models\PublicRelation;

use App\Models\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 企业投诉模型 - 公关维权-我要投诉-企业类
 *
 * @property int $id
 * @property string $report_type
 * @property string $human_name
 * @property array|null $enterprise_material
 * @property array|null $contact_material
 * @property array|null $report_material
 * @property array|null $proof_material
 * @property string $site_name
 * @property string $account_name
 * @property array|null $item_url
 * @property string|null $report_content
 * @property string $proof_type
 * @property string $send_email
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
class ComplaintEnterprise extends Model
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
     * Report Type: 涉企类
     */
    public const REPORT_TYPE_ENTERPRISE = '涉企类';

    // ==================== 证据种类常量 ====================

    /**
     * Proof Type Options: 证据种类枚举列表
     */
    public const PROOF_TYPE_OPTIONS = [
        '法院判决书、行政处罚决定书等生效法律文书',
        '司法、执法、执纪及其他相关职权部门对外公开发布的公告通告',
        '司法、执法、执纪及其他相关职权部门出具的证明',
        '主管主办单位出具的证明',
        '国家依法批准成立的具有特定资质的第三方机构出具的证明',
        '颁发新闻媒体出具的撤稿函',
        '有关职能部门公共查询服务平台的查询结果',
        '其他能够证明举报信息内容构成侵权的材料',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'complaint_enterprise';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'report_type',
        'material_id',
        'human_name',
        'enterprise_material',
        'contact_material',
        'report_material',
        'proof_material',
        'site_name',
        'account_name',
        'item_url',
        'report_content',
        'proof_type',
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
        'enterprise_material' => 'array',
        'contact_material' => 'array',
        'report_material' => 'array',
        'proof_material' => 'array',
        'proof_type' => 'array',
        'item_url' => 'array',
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

    /**
     * Get the proof type label.
     * Returns the proof_type value if it exists in PROOF_TYPE_OPTIONS, otherwise returns '未知'.
     *
     * @return string
     */
    public function getProofTypeLabelAttribute(): string
    {
        if (in_array($this->proof_type, self::PROOF_TYPE_OPTIONS, true)) {
            return $this->proof_type;
        }

        return '未知';
    }
}
