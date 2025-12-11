<?php

declare(strict_types=1);

namespace App\Models\PublicRelation;

use App\Models\Traits\HasAuditFields;
use App\Models\Traits\HasStatusScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 企业资料模型
 *
 * @property int $id
 * @property string|null $name
 * @property array|null $enterprise_material
 * @property string|null $type
 * @property string|null $nature
 * @property string|null $industry
 * @property string|null $contact_identity
 * @property string|null $contact_name
 * @property string|null $contact_phone
 * @property string|null $contact_email
 * @property array|null $report_material
 * @property array|null $proof_material
 * @property int $status
 * @property string|null $create_by
 * @property string|null $update_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class MaterialEnterprise extends Model
{
    use SoftDeletes;
    // use HasStatusScope;
    // use HasAuditFields;

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

    // ==================== 企业类型常量 ====================

    /**
     * Enterprise Type: State-owned
     */
    public const TYPE_STATE_OWNED = '国有企业';

    /**
     * Enterprise Type: Private
     */
    public const TYPE_PRIVATE = '民营企业';

    /**
     * Enterprise Type: Foreign
     */
    public const TYPE_FOREIGN = '外资企业';

    /**
     * Enterprise Type: Other
     */
    public const TYPE_OTHER = '其他企业';

    /**
     * Enterprise type options
     */
    public const TYPE_OPTIONS = [
        self::TYPE_STATE_OWNED,
        self::TYPE_PRIVATE,
        self::TYPE_FOREIGN,
        self::TYPE_OTHER,
    ];

    // ==================== 企业性质常量 ====================

    /**
     * Enterprise Nature: Listed
     */
    public const NATURE_LISTED = '上市企业';

    /**
     * Enterprise Nature: Pre-IPO
     */
    public const NATURE_PRE_IPO = '拟上市企业';

    /**
     * Enterprise Nature: Other
     */
    public const NATURE_OTHER = '其它企业';

    /**
     * Enterprise nature options
     */
    public const NATURE_OPTIONS = [
        self::NATURE_LISTED,
        self::NATURE_PRE_IPO,
        self::NATURE_OTHER,
    ];

    // ==================== 行业分类常量 ====================

    /**
     * Industry: Agriculture, Forestry, Animal Husbandry, Fishery
     */
    public const INDUSTRY_AGRICULTURE = '农、林、牧、渔业';

    /**
     * Industry: Mining
     */
    public const INDUSTRY_MINING = '采矿业';

    /**
     * Industry: Manufacturing
     */
    public const INDUSTRY_MANUFACTURING = '制造业';

    /**
     * Industry: Electricity, Heat, Gas and Water Production
     */
    public const INDUSTRY_UTILITIES = '电力、热力、燃气及水生产和供应业';

    /**
     * Industry: Construction
     */
    public const INDUSTRY_CONSTRUCTION = '建筑业';

    /**
     * Industry: Wholesale and Retail
     */
    public const INDUSTRY_WHOLESALE_RETAIL = '批发和零售业';

    /**
     * Industry: Transportation, Warehousing and Postal Services
     */
    public const INDUSTRY_TRANSPORTATION = '交通运输、仓储和邮政业';

    /**
     * Industry: Accommodation and Catering
     */
    public const INDUSTRY_HOSPITALITY = '住宿和餐饮业';

    /**
     * Industry: Information Technology
     */
    public const INDUSTRY_IT = '信息传输、软件和信息技术服务业';

    /**
     * Industry: Finance
     */
    public const INDUSTRY_FINANCE = '金融业';

    /**
     * Industry: Real Estate
     */
    public const INDUSTRY_REAL_ESTATE = '房地产业';

    /**
     * Industry: Leasing and Business Services
     */
    public const INDUSTRY_LEASING = '租赁和商务服务业';

    /**
     * Industry: Scientific Research and Technical Services
     */
    public const INDUSTRY_RESEARCH = '科学研究和技术服务业';

    /**
     * Industry: Water Conservancy, Environment and Public Facilities
     */
    public const INDUSTRY_PUBLIC_FACILITIES = '水利、环境和公共设施管理业';

    /**
     * Industry: Resident Services, Repair and Other Services
     */
    public const INDUSTRY_RESIDENT_SERVICES = '居民服务、修理和其他服务业';

    /**
     * Industry: Education
     */
    public const INDUSTRY_EDUCATION = '教育';

    /**
     * Industry: Health and Social Work
     */
    public const INDUSTRY_HEALTH = '卫生、社会工作';

    /**
     * Industry: Culture, Sports and Entertainment
     */
    public const INDUSTRY_CULTURE = '文化、体育和娱乐业';

    /**
     * Industry options
     */
    public const INDUSTRY_OPTIONS = [
        self::INDUSTRY_AGRICULTURE,
        self::INDUSTRY_MINING,
        self::INDUSTRY_MANUFACTURING,
        self::INDUSTRY_UTILITIES,
        self::INDUSTRY_CONSTRUCTION,
        self::INDUSTRY_WHOLESALE_RETAIL,
        self::INDUSTRY_TRANSPORTATION,
        self::INDUSTRY_HOSPITALITY,
        self::INDUSTRY_IT,
        self::INDUSTRY_FINANCE,
        self::INDUSTRY_REAL_ESTATE,
        self::INDUSTRY_LEASING,
        self::INDUSTRY_RESEARCH,
        self::INDUSTRY_PUBLIC_FACILITIES,
        self::INDUSTRY_RESIDENT_SERVICES,
        self::INDUSTRY_EDUCATION,
        self::INDUSTRY_HEALTH,
        self::INDUSTRY_CULTURE,
    ];

    // ==================== 联系人身份常量 ====================

    /**
     * Contact Identity: Legal or Lawyer
     */
    public const CONTACT_IDENTITY_LEGAL = '企业法务或委托律师';

    /**
     * Contact Identity: Other Staff
     */
    public const CONTACT_IDENTITY_OTHER_STAFF = '企业其它工作人员';

    /**
     * Contact identity options
     */
    public const CONTACT_IDENTITY_OPTIONS = [
        self::CONTACT_IDENTITY_LEGAL,
        self::CONTACT_IDENTITY_OTHER_STAFF,
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'material_enterprise';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'enterprise_material',
        'type',
        'nature',
        'industry',
        'contact_identity',
        'contact_name',
        'contact_phone',
        'contact_email',
        'report_material',
        'proof_material',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'enterprise_material' => 'array',
        'report_material' => 'array',
        'proof_material' => 'array',
        'status' => 'integer',
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
}
