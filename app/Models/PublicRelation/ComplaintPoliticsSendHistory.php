<?php

declare(strict_types=1);

namespace App\Models\PublicRelation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 政治类投诉邮件发送历史记录模型
 *
 * 用于记录每次政治类投诉邮件发送的详细信息，包括收件人、操作人、邮件数据和渲染后的HTML内容
 *
 * @property int $id 主键ID
 * @property int $complaint_id 关联的政治类投诉记录ID
 * @property string $recipient_email 收件人邮箱地址
 * @property int $operator_id 操作人ID
 * @property string $operator_name 操作人姓名
 * @property array|null $mail_data 邮件数据(JSON格式)
 * @property string|null $rendered_html 渲染后的完整HTML内容
 * @property int $send_status 发送状态: 1-成功 2-失败
 * @property string|null $error_message 失败时的错误信息
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @property-read string $send_status_label 发送状态标签
 * @property-read ComplaintPolitics|null $complaint 关联的政治类投诉记录
 */
class ComplaintPoliticsSendHistory extends Model
{
    // ==================== 发送状态常量 ====================

    /**
     * 发送状态: 成功
     */
    public const SEND_STATUS_SUCCESS = 1;

    /**
     * 发送状态: 失败
     */
    public const SEND_STATUS_FAILED = 2;

    /**
     * 发送状态标签数组
     */
    public const SEND_STATUS_LABELS = [
        self::SEND_STATUS_SUCCESS => '成功',
        self::SEND_STATUS_FAILED => '失败',
    ];

    /**
     * 模型关联的数据表名称
     *
     * @var string
     */
    protected $table = 'complaint_politics_send_history';

    /**
     * 可批量赋值的属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'complaint_id',
        'recipient_email',
        'operator_id',
        'operator_name',
        'mail_data',
        'rendered_html',
        'send_status',
        'error_message',
    ];

    /**
     * 属性类型转换
     *
     * @var array<string, string>
     */
    protected $casts = [
        'complaint_id' => 'integer',
        'operator_id' => 'integer',
        'mail_data' => 'array',
        'send_status' => 'integer',
    ];

    /**
     * 获取发送状态标签
     *
     * @return string 发送状态的中文标签
     */
    public function getSendStatusLabelAttribute(): string
    {
        return self::SEND_STATUS_LABELS[$this->send_status] ?? '未知';
    }

    /**
     * 关联政治类投诉记录
     *
     * @return BelongsTo<ComplaintPolitics, ComplaintPoliticsSendHistory>
     */
    public function complaint(): BelongsTo
    {
        return $this->belongsTo(ComplaintPolitics::class, 'complaint_id', 'id');
    }
}
