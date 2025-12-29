<?php

declare(strict_types=1);

namespace App\Models\PublicRelation;

use App\Models\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 政治类举报模型 - 公关维权-我要举报-政治类
 *
 * @property int $id
 * @property string $report_type
 * @property string $report_sub_type
 * @property string $report_platform
 * @property string $site_name
 * @property array|null $site_url
 * @property string $app_name
 * @property string $app_location
 * @property array|null $app_url
 * @property string $account_platform
 * @property string $account_nature
 * @property string $account_name
 * @property string $account_platform_name
 * @property array|null $account_url
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
 * @property int $create_by 创建人ID
 * @property int $update_by 更新人ID
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class ComplaintPolitics extends Model
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

    /**
     * Report Type: 政治类
     */
    public const REPORT_TYPE_POLITICS = '政治类';

    // ==================== 危害小类常量 ====================

    /**
     * 危害小类: 使用传播违法违规宗教类APP
     */
    public const REPORT_SUB_TYPE_ILLEGAL_RELIGION_APP = '使用传播违法违规宗教类APP';

    /**
     * 危害小类: 攻击党和国家制度及重大方针政策
     */
    public const REPORT_SUB_TYPE_ATTACK_PARTY_POLICY = '攻击党和国家制度及重大方针政策';

    /**
     * 危害小类: 攻击"两个维护"
     */
    public const REPORT_SUB_TYPE_ATTACK_TWO_SAFEGUARDS = '攻击“两个维护”';

    /**
     * 危害小类: 危害国家安全、泄露国家秘密
     */
    public const REPORT_SUB_TYPE_NATIONAL_SECURITY = '危害国家安全、泄露国家秘密';

    /**
     * 危害小类: 破坏国家统一和领土完整
     */
    public const REPORT_SUB_TYPE_NATIONAL_UNITY = '破坏国家统一和领土完整';

    /**
     * 危害小类: 损害国家形象荣誉利益
     */
    public const REPORT_SUB_TYPE_NATIONAL_IMAGE = '损害国家形象荣誉利益';

    /**
     * 危害小类: 破坏国家民族宗教政策、宣扬邪教
     */
    public const REPORT_SUB_TYPE_RELIGIOUS_POLICY = '破坏国家民族宗教政策、宣扬邪教';

    /**
     * 危害小类: 诋毁英雄烈士
     */
    public const REPORT_SUB_TYPE_DEFAME_HEROES = '诋毁英雄烈士';

    /**
     * 危害小类枚举选项 - 政治类举报的8种危害小类
     */
    public const REPORT_SUB_TYPE_OPTIONS = [
        self::REPORT_SUB_TYPE_ILLEGAL_RELIGION_APP,
        self::REPORT_SUB_TYPE_ATTACK_PARTY_POLICY,
        self::REPORT_SUB_TYPE_ATTACK_TWO_SAFEGUARDS,
        self::REPORT_SUB_TYPE_NATIONAL_SECURITY,
        self::REPORT_SUB_TYPE_NATIONAL_UNITY,
        self::REPORT_SUB_TYPE_NATIONAL_IMAGE,
        self::REPORT_SUB_TYPE_RELIGIOUS_POLICY,
        self::REPORT_SUB_TYPE_DEFAME_HEROES,
    ];

    // ==================== 被举报平台常量 ====================

    /**
     * Report Platform: 网站网页
     */
    public const REPORT_PLATFORM_WEBSITE = '网站网页';

    /**
     * Report Platform: APP
     */
    public const REPORT_PLATFORM_APP = 'APP';

    /**
     * Report Platform: 网络账号
     */
    public const REPORT_PLATFORM_ACCOUNT = '网络账号';

    /**
     * Report platform options
     */
    public const REPORT_PLATFORM_OPTIONS = [
        self::REPORT_PLATFORM_WEBSITE,
        self::REPORT_PLATFORM_APP,
        self::REPORT_PLATFORM_ACCOUNT,
    ];

    // ==================== APP定位常量 ====================

    /**
     * APP Location: 有害信息链接
     */
    public const APP_LOCATION_HARMFUL_LINK = '有害信息链接';

    /**
     * APP Location: APP官方网址
     */
    public const APP_LOCATION_OFFICIAL_URL = 'APP官方网址';

    /**
     * APP Location: APP下载地址
     */
    public const APP_LOCATION_DOWNLOAD_URL = 'APP下载地址';

    /**
     * APP location options
     */
    public const APP_LOCATION_OPTIONS = [
        self::APP_LOCATION_HARMFUL_LINK,
        self::APP_LOCATION_OFFICIAL_URL,
        self::APP_LOCATION_DOWNLOAD_URL,
    ];

    // ==================== 账号平台常量 ====================

    /**
     * Account Platform: 微信
     */
    public const ACCOUNT_PLATFORM_WECHAT = '微信';

    /**
     * Account Platform: QQ
     */
    public const ACCOUNT_PLATFORM_QQ = 'QQ';

    /**
     * Account Platform: 微博
     */
    public const ACCOUNT_PLATFORM_WEIBO = '微博';

    /**
     * Account Platform: 贴吧
     */
    public const ACCOUNT_PLATFORM_TIEBA = '贴吧';

    /**
     * Account Platform: 博客
     */
    public const ACCOUNT_PLATFORM_BLOG = '博客';

    /**
     * Account Platform: 直播平台
     */
    public const ACCOUNT_PLATFORM_LIVE = '直播平台';

    /**
     * Account Platform: 论坛社区
     */
    public const ACCOUNT_PLATFORM_FORUM = '论坛社区';

    /**
     * Account Platform: 网盘
     */
    public const ACCOUNT_PLATFORM_CLOUD = '网盘';

    /**
     * Account Platform: 音频
     */
    public const ACCOUNT_PLATFORM_AUDIO = '音频';

    /**
     * Account Platform: 其他
     */
    public const ACCOUNT_PLATFORM_OTHER = '其他';

    /**
     * Account Platform Options
     */
    public const ACCOUNT_PLATFORM_OPTIONS = [
        self::ACCOUNT_PLATFORM_WECHAT,
        self::ACCOUNT_PLATFORM_QQ,
        self::ACCOUNT_PLATFORM_WEIBO,
        self::ACCOUNT_PLATFORM_TIEBA,
        self::ACCOUNT_PLATFORM_BLOG,
        self::ACCOUNT_PLATFORM_LIVE,
        self::ACCOUNT_PLATFORM_FORUM,
        self::ACCOUNT_PLATFORM_CLOUD,
        self::ACCOUNT_PLATFORM_AUDIO,
        self::ACCOUNT_PLATFORM_OTHER,
    ];

    /**
     * 需要填写账号平台名称的平台类型
     */
    public const ACCOUNT_PLATFORM_NEED_NAME = [
        self::ACCOUNT_PLATFORM_BLOG,
        self::ACCOUNT_PLATFORM_LIVE,
        self::ACCOUNT_PLATFORM_FORUM,
        self::ACCOUNT_PLATFORM_CLOUD,
        self::ACCOUNT_PLATFORM_AUDIO,
        self::ACCOUNT_PLATFORM_OTHER,
    ];

    // ==================== 账号性质常量 ====================

    /**
     * Account Nature: 个人
     */
    public const ACCOUNT_NATURE_PERSONAL = '个人';

    /**
     * Account Nature: 公众
     */
    public const ACCOUNT_NATURE_PUBLIC = '公众';

    /**
     * Account Nature: 群组
     */
    public const ACCOUNT_NATURE_GROUP = '群组';

    /**
     * Account Nature: 认证
     */
    public const ACCOUNT_NATURE_VERIFIED = '认证';

    /**
     * Account Nature: 非认证
     */
    public const ACCOUNT_NATURE_UNVERIFIED = '非认证';

    /**
     * 微信账号性质
     */
    public const ACCOUNT_NATURE_WECHAT_OPTIONS = [
        self::ACCOUNT_NATURE_PERSONAL,
        self::ACCOUNT_NATURE_PUBLIC,
        self::ACCOUNT_NATURE_GROUP,
    ];

    /**
     * QQ账号性质
     */
    public const ACCOUNT_NATURE_QQ_OPTIONS = [
        self::ACCOUNT_NATURE_PERSONAL,
        self::ACCOUNT_NATURE_GROUP,
    ];

    /**
     * 微博账号性质
     */
    public const ACCOUNT_NATURE_WEIBO_OPTIONS = [
        self::ACCOUNT_NATURE_VERIFIED,
        self::ACCOUNT_NATURE_UNVERIFIED,
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'complaint_politics';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'report_type',
        'report_sub_type',
        'report_platform',
        'site_name',
        'site_url',
        'app_name',
        'app_location',
        'app_url',
        'account_platform',
        'account_nature',
        'account_name',
        'account_platform_name',
        'account_url',
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
        'app_url' => 'array',
        'account_url' => 'array',
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

    /**
     * Get the report platform label.
     *
     * @return string
     */
    public function getReportPlatformLabelAttribute(): string
    {
        if (in_array($this->report_platform, self::REPORT_PLATFORM_OPTIONS, true)) {
            return $this->report_platform;
        }

        return '未知';
    }

    /**
     * 根据账号平台获取对应的账号性质选项
     *
     * @param string $platform
     * @return array
     */
    public static function getAccountNatureOptions(string $platform): array
    {
        switch ($platform) {
            case self::ACCOUNT_PLATFORM_WECHAT:
                return self::ACCOUNT_NATURE_WECHAT_OPTIONS;
            case self::ACCOUNT_PLATFORM_QQ:
                return self::ACCOUNT_NATURE_QQ_OPTIONS;
            case self::ACCOUNT_PLATFORM_WEIBO:
                return self::ACCOUNT_NATURE_WEIBO_OPTIONS;
            default:
                return [];
        }
    }

    /**
     * 判断账号平台是否需要填写平台名称
     *
     * @param string $platform
     * @return bool
     */
    public static function needAccountPlatformName(string $platform): bool
    {
        return in_array($platform, self::ACCOUNT_PLATFORM_NEED_NAME, true);
    }
}
