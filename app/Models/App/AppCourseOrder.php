<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppCourseOrder extends Model
{
    use HasFactory;

    protected $table = 'app_course_order';
    protected $primaryKey = 'order_id';
    public $timestamps = false;

    // 支付状态
    const PAY_STATUS_PENDING = 0;    // 待支付
    const PAY_STATUS_PAID = 1;       // 已支付
    const PAY_STATUS_REFUNDED = 2;   // 已退款
    const PAY_STATUS_CLOSED = 3;     // 已关闭

    // 支付方式
    const PAY_TYPE_WECHAT = 1;       // 微信
    const PAY_TYPE_ALIPAY = 2;       // 支付宝
    const PAY_TYPE_BALANCE = 3;      // 余额
    const PAY_TYPE_FREE = 4;         // 免费

    // 退款状态
    const REFUND_STATUS_NONE = 0;       // 无
    const REFUND_STATUS_APPLYING = 1;   // 申请中
    const REFUND_STATUS_REFUNDED = 2;   // 已退款
    const REFUND_STATUS_REJECTED = 3;   // 已拒绝

    // 佣金状态
    const COMMISSION_STATUS_PENDING = 0;   // 待结算
    const COMMISSION_STATUS_SETTLED = 1;   // 已结算

    protected $fillable = [
        'order_no',
        'member_id',
        'course_id',
        'course_title',
        'course_cover',
        'original_price',
        'current_price',
        'discount_amount',
        'coupon_amount',
        'point_deduct',
        'point_amount',
        'paid_amount',
        'coupon_id',
        'promotion_type',
        'promotion_id',
        'pay_status',
        'pay_type',
        'pay_trade_no',
        'pay_time',
        'expire_time',
        'refund_status',
        'refund_amount',
        'refund_reason',
        'refund_time',
        'inviter_id',
        'commission_amount',
        'commission_status',
        'remark',
        'client_ip',
        'user_agent',
        'create_time',
        'update_time',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'member_id' => 'integer',
        'course_id' => 'integer',
        'original_price' => 'decimal:2',
        'current_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'coupon_amount' => 'decimal:2',
        'point_deduct' => 'integer',
        'point_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'pay_status' => 'integer',
        'pay_type' => 'integer',
        'pay_time' => 'datetime',
        'expire_time' => 'datetime',
        'refund_status' => 'integer',
        'refund_amount' => 'decimal:2',
        'refund_time' => 'datetime',
        'inviter_id' => 'integer',
        'commission_amount' => 'decimal:2',
        'commission_status' => 'integer',
        'create_time' => 'datetime',
        'update_time' => 'datetime',
    ];

    /**
     * 关联用户
     */
    public function member()
    {
        return $this->belongsTo(AppMemberBase::class, 'member_id', 'member_id');
    }

    /**
     * 关联课程
     */
    public function course()
    {
        return $this->belongsTo(AppCourseBase::class, 'course_id', 'course_id');
    }

    /**
     * 查询作用域：按用户
     */
    public function scopeByMember($query, int $memberId)
    {
        return $query->where('member_id', $memberId);
    }

    /**
     * 查询作用域：待支付
     */
    public function scopePending($query)
    {
        return $query->where('pay_status', self::PAY_STATUS_PENDING);
    }

    /**
     * 查询作用域：已支付
     */
    public function scopePaid($query)
    {
        return $query->where('pay_status', self::PAY_STATUS_PAID);
    }

    /**
     * 是否待支付
     */
    public function isPending(): bool
    {
        return $this->pay_status === self::PAY_STATUS_PENDING;
    }

    /**
     * 是否已支付
     */
    public function isPaid(): bool
    {
        return $this->pay_status === self::PAY_STATUS_PAID;
    }

    /**
     * 是否已过期
     */
    public function isExpired(): bool
    {
        return $this->expire_time && $this->expire_time < now();
    }

    /**
     * 生成订单号
     */
    public static function generateOrderNo(): string
    {
        return 'CO' . date('YmdHis') . mt_rand(100000, 999999);
    }

    /**
     * 标记已支付
     */
    public function markPaid(int $payType, ?string $tradeNo = null): bool
    {
        $this->pay_status = self::PAY_STATUS_PAID;
        $this->pay_type = $payType;
        $this->pay_trade_no = $tradeNo;
        $this->pay_time = now();
        $this->update_time = now();
        return $this->save();
    }

    /**
     * 关闭订单
     */
    public function close(): bool
    {
        $this->pay_status = self::PAY_STATUS_CLOSED;
        $this->update_time = now();
        return $this->save();
    }

    /**
     * 获取支付状态文本
     */
    public function getPayStatusTextAttribute(): string
    {
        $map = [
            self::PAY_STATUS_PENDING => '待支付',
            self::PAY_STATUS_PAID => '已支付',
            self::PAY_STATUS_REFUNDED => '已退款',
            self::PAY_STATUS_CLOSED => '已关闭',
        ];

        return $map[$this->pay_status] ?? '未知';
    }
}
