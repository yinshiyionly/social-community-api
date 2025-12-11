<?php

declare(strict_types=1);

namespace App\Models\PublicRelation;

use App\Models\Traits\HasAuditFields;
use App\Models\Traits\HasStatusScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 诽谤资料模型
 *
 * @property int $id
 * @property string|null $report_subject 举报主体
 * @property string|null $occupation_category 从业类别
 * @property string|null $enterprise_name 单位名称
 * @property string|null $contact_phone 有效电话
 * @property string|null $contact_email 电子邮件
 * @property string|null $real_name 真实姓名
 * @property array|null $report_material 举报材料
 * @property int $status 状态
 * @property string|null $create_by 创建人
 * @property string|null $update_by 更新人
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class MaterialDefamation extends Model
{
    use SoftDeletes;
    use HasStatusScope;
    use HasAuditFields;

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

    // ==================== 举报主体常量 ====================

    /**
     * 举报主体: 公民
     */
    public const REPORT_SUBJECT_CITIZEN = '公民';

    /**
     * 举报主体: 法人及其他组织
     */
    public const REPORT_SUBJECT_ORGANIZATION = '法人及其他组织';

    /**
     * 举报主体选项
     */
    public const REPORT_SUBJECT_OPTIONS = [
        self::REPORT_SUBJECT_CITIZEN,
        self::REPORT_SUBJECT_ORGANIZATION,
    ];

    // ==================== 从业类别常量 ====================

    /**
     * 从业类别: 企业人员
     */
    public const OCCUPATION_ENTERPRISE = '企业人员';

    /**
     * 从业类别: 事业单位人员
     */
    public const OCCUPATION_INSTITUTION = '事业单位人员';

    /**
     * 从业类别: 公务员
     */
    public const OCCUPATION_CIVIL_SERVANT = '公务员';

    /**
     * 从业类别: 学生
     */
    public const OCCUPATION_STUDENT = '学生';

    /**
     * 从业类别: 自由职业者
     */
    public const OCCUPATION_FREELANCER = '自由职业者';

    /**
     * 从业类别: 其它
     */
    public const OCCUPATION_OTHER = '其它';

    /**
     * 从业类别选项
     */
    public const OCCUPATION_OPTIONS = [
        self::OCCUPATION_ENTERPRISE,
        self::OCCUPATION_INSTITUTION,
        self::OCCUPATION_CIVIL_SERVANT,
        self::OCCUPATION_STUDENT,
        self::OCCUPATION_FREELANCER,
        self::OCCUPATION_OTHER,
    ];

    /**
     * 表名
     *
     * @var string
     */
    protected $table = 'material_defamation';

    /**
     * 可批量赋值的字段
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'report_subject',
        'occupation_category',
        'enterprise_name',
        'contact_phone',
        'contact_email',
        'real_name',
        'report_material',
        'status',
    ];

    /**
     * 字段类型转换
     *
     * @var array<string, string>
     */
    protected $casts = [
        'report_material' => 'array',
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
}
