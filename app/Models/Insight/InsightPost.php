<?php

declare(strict_types=1);

namespace App\Models\Insight;

use Illuminate\Database\Eloquent\Model;

/**
 * 内容洞察数据模型
 *
 * 用于存储从 Kafka 同步过来的舆情数据
 *
 * @property string $origin_id 原始ID（主键）
 * @property string $post_id 发文ID
 * @property \Illuminate\Support\Carbon|null $publish_time 发布时间
 * @property \Illuminate\Support\Carbon|null $push_ready_time 可消费时间
 * @property string $main_domain 主域名
 * @property string $domain 子域名
 * @property string $url 网页URL
 * @property string|null $title 标题
 * @property array|null $feature 算法信息
 * @property array|null $poi POI信息
 * @property int $status 视频状态
 * @property int $post_type 发文类型
 * @property array|null $video_info 视频信息
 * @property array|null $based_location 相关位置
 * @property array|null $matched_task_ids 命中任务ID
 * @property int $process_state 处理状态
 */
class InsightPost extends Model
{
    // ==================== 处理状态常量 ====================

    /**
     * 处理状态: 未处理
     */
    public const PROCESS_STATE_PENDING = 0;

    /**
     * 处理状态: 已处理
     */
    public const PROCESS_STATE_PROCESSED = 1;

    /**
     * 处理状态: 未知
     */
    public const PROCESS_STATE_UNKNOWN = 2;

    /**
     * 处理状态标签映射
     */
    public const PROCESS_STATE_LABELS = [
        self::PROCESS_STATE_PENDING => '未处理',
        self::PROCESS_STATE_PROCESSED => '已处理',
        self::PROCESS_STATE_UNKNOWN => '未知',
    ];

    // ==================== 发文类型常量 ====================

    /**
     * 发文类型: 类型1
     */
    public const POST_TYPE_1 = 1;

    /**
     * 发文类型: 类型2
     */
    public const POST_TYPE_2 = 2;

    /**
     * 发文类型: 类型10
     */
    public const POST_TYPE_10 = 10;

    /**
     * 需要处理的发文类型
     */
    public const VALID_POST_TYPES = [
        self::POST_TYPE_1,
        self::POST_TYPE_2,
        self::POST_TYPE_10,
    ];

    /**
     * 表名
     *
     * @var string
     */
    protected $table = 'insight_post_1';

    /**
     * 主键字段
     *
     * @var string
     */
    protected $primaryKey = 'origin_id';

    /**
     * 主键是否自增
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * 主键类型
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * 是否使用时间戳
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * 可批量赋值的字段
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'origin_id',
        'post_id',
        'publish_time',
        'push_ready_time',
        'main_domain',
        'domain',
        'url',
        'title',
        'feature',
        'poi',
        'status',
        'post_type',
        'video_info',
        'based_location',
        'matched_task_ids',
        'process_state',
    ];

    /**
     * 字段类型转换
     *
     * @var array<string, string>
     */
    protected $casts = [
        'feature' => 'array',
        'poi' => 'array',
        'video_info' => 'array',
        'based_location' => 'array',
        'publish_time' => 'datetime',
        'push_ready_time' => 'datetime',
        'status' => 'integer',
        'post_type' => 'integer',
        'process_state' => 'integer',
        'matched_task_ids' => 'array'
    ];

    /**
     * 获取处理状态标签
     *
     * @return string
     */
    public function getProcessStateLabelAttribute(): string
    {
        return self::PROCESS_STATE_LABELS[$this->process_state] ?? '未知';
    }

    /**
     * 判断是否为有效的发文类型
     *
     * @return bool
     */
    public function isValidPostType(): bool
    {
        return in_array($this->post_type, self::VALID_POST_TYPES, true);
    }
}
