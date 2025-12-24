<?php

declare(strict_types=1);

namespace App\Models\PublicRelation;

use App\Models\Traits\HasAuditFields;
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

    // ==================== 从业类别常量（公民） ====================

    /**
     * 从业类别(公民): 企业人员
     */
    public const OCCUPATION_CITIZEN_ENTERPRISE = '企业人员';

    /**
     * 从业类别(公民): 事业单位人员
     */
    public const OCCUPATION_CITIZEN_INSTITUTION = '事业单位人员';

    /**
     * 从业类别(公民): 公务员
     */
    public const OCCUPATION_CITIZEN_CIVIL_SERVANT = '公务员';

    /**
     * 从业类别(公民): 学生
     */
    public const OCCUPATION_CITIZEN_STUDENT = '学生';

    /**
     * 从业类别(公民): 自由职业者
     */
    public const OCCUPATION_CITIZEN_FREELANCER = '自由职业者';

    /**
     * 从业类别(公民): 其他
     */
    public const OCCUPATION_CITIZEN_OTHER = '其他';

    /**
     * 从业类别选项（公民）
     */
    public const OCCUPATION_CITIZEN_OPTIONS = [
        self::OCCUPATION_CITIZEN_ENTERPRISE,
        self::OCCUPATION_CITIZEN_INSTITUTION,
        self::OCCUPATION_CITIZEN_CIVIL_SERVANT,
        self::OCCUPATION_CITIZEN_STUDENT,
        self::OCCUPATION_CITIZEN_FREELANCER,
        self::OCCUPATION_CITIZEN_OTHER,
    ];

    // ==================== 从业类别常量（法人及其他组织） ====================

    /**
     * 从业类别(组织): 上市企业法人
     */
    public const OCCUPATION_ORG_LISTED_ENTERPRISE = '上市企业法人';

    /**
     * 从业类别(组织): 拟上市企业法人
     */
    public const OCCUPATION_ORG_PRE_LISTED_ENTERPRISE = '拟上市企业法人';

    /**
     * 从业类别(组织): 其他企业法人
     */
    public const OCCUPATION_ORG_OTHER_ENTERPRISE = '其他企业法人';

    /**
     * 从业类别(组织): 机关法人
     */
    public const OCCUPATION_ORG_GOVERNMENT = '机关法人';

    /**
     * 从业类别(组织): 事业单位法人
     */
    public const OCCUPATION_ORG_INSTITUTION = '事业单位法人';

    /**
     * 从业类别(组织): 社会团体法人
     */
    public const OCCUPATION_ORG_SOCIAL_GROUP = '社会团体法人';

    /**
     * 从业类别(组织): 其他组织
     */
    public const OCCUPATION_ORG_OTHER = '其他组织';

    /**
     * 从业类别选项（法人及其他组织）
     */
    public const OCCUPATION_ORGANIZATION_OPTIONS = [
        self::OCCUPATION_ORG_LISTED_ENTERPRISE,
        self::OCCUPATION_ORG_PRE_LISTED_ENTERPRISE,
        self::OCCUPATION_ORG_OTHER_ENTERPRISE,
        self::OCCUPATION_ORG_GOVERNMENT,
        self::OCCUPATION_ORG_INSTITUTION,
        self::OCCUPATION_ORG_SOCIAL_GROUP,
        self::OCCUPATION_ORG_OTHER,
    ];

    /**
     * 根据举报主体获取从业类别选项映射
     */
    public const OCCUPATION_OPTIONS_BY_SUBJECT = [
        self::REPORT_SUBJECT_CITIZEN => self::OCCUPATION_CITIZEN_OPTIONS,
        self::REPORT_SUBJECT_ORGANIZATION => self::OCCUPATION_ORGANIZATION_OPTIONS,
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

    /**
     * 根据举报主体获取从业类别选项
     *
     * @param string|null $reportSubject 举报主体
     * @return array
     */
    public static function getOccupationOptions(?string $reportSubject): array
    {
        return self::OCCUPATION_OPTIONS_BY_SUBJECT[$reportSubject] ?? [];
    }
}
