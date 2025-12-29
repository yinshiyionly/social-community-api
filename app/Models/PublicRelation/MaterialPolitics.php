<?php

declare(strict_types=1);

namespace App\Models\PublicRelation;

use App\Models\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 政治类资料模型
 *
 * @property int $id
 * @property string|null $name 姓名
 * @property int $gender 性别
 * @property string|null $contact_phone 有效电话
 * @property string|null $contact_email 电子邮件
 * @property int|null $province_code 省份code
 * @property int|null $city_code 城市code
 * @property int|null $district_code 区县code
 * @property string|null $contact_address 通讯地址
 * @property array|null $report_material 举报材料
 * @property int $status 状态
 * @property int $create_by 创建人ID
 * @property int $update_by 更新人ID
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class MaterialPolitics extends Model
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

    // ==================== 性别常量 ====================

    /**
     * Gender: Unknown
     */
    public const GENDER_UNKNOWN = 0;

    /**
     * Gender: Male
     */
    public const GENDER_MALE = 1;

    /**
     * Gender: Female
     */
    public const GENDER_FEMALE = 2;

    /**
     * Gender labels
     */
    public const GENDER_LABELS = [
        self::GENDER_UNKNOWN => '未知',
        self::GENDER_MALE => '男',
        self::GENDER_FEMALE => '女',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'material_politics';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'gender',
        'contact_phone',
        'contact_email',
        'province_code',
        'city_code',
        'district_code',
        'contact_address',
        'report_material',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'report_material' => 'array',
        'gender' => 'integer',
        'status' => 'integer',
        'province_code' => 'integer',
        'city_code' => 'integer',
        'district_code' => 'integer',
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
     * Get the gender label.
     *
     * @return string
     */
    public function getGenderLabelAttribute(): string
    {
        return self::GENDER_LABELS[$this->gender] ?? '未知';
    }
}
