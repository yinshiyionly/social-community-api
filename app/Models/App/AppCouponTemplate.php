<?php

namespace App\Models\App;

use App\Models\Traits\HasOperator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppCouponTemplate extends Model
{
    use HasFactory, SoftDeletes, HasOperator;

    protected $table = 'app_coupon_template';
    protected $primaryKey = 'coupon_id';

    // 优惠券类型
    const TYPE_FULL_REDUCE = 1;   // 满减券
    const TYPE_DISCOUNT = 2;      // 折扣券
    const TYPE_NO_THRESHOLD = 3;  // 无门槛券

    // 适用范围
    const SCOPE_ALL = 1;          // 全部课程
    const SCOPE_CATEGORY = 2;     // 指定分类
    const SCOPE_COURSE = 3;       // 指定课程

    // 有效期类型
    const VALID_TYPE_FIXED = 1;   // 固定时间
    const VALID_TYPE_DAYS = 2;    // 领取后N天

    // 领取方式
    const RECEIVE_TYPE_PUBLIC = 1;   // 公开领取
    const RECEIVE_TYPE_SYSTEM = 2;   // 系统发放
    const RECEIVE_TYPE_CODE = 3;     // 兑换码

    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 2;

    protected $fillable = [
        'coupon_name',
        'coupon_type',
        'threshold_amount',
        'discount_amount',
        'discount_rate',
        'max_discount',
        'scope_type',
        'scope_ids',
        'total_count',
        'issued_count',
        'used_count',
        'per_limit',
        'valid_type',
        'valid_start_time',
        'valid_end_time',
        'valid_days',
        'receive_type',
        'receive_start_time',
        'receive_end_time',
        'sort_order',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'coupon_id' => 'integer',
        'coupon_type' => 'integer',
        'threshold_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'scope_type' => 'integer',
        'scope_ids' => 'array',
        'total_count' => 'integer',
        'issued_count' => 'integer',
        'used_count' => 'integer',
        'per_limit' => 'integer',
        'valid_type' => 'integer',
        'valid_start_time' => 'datetime',
        'valid_end_time' => 'datetime',
        'valid_days' => 'integer',
        'receive_type' => 'integer',
        'receive_start_time' => 'datetime',
        'receive_end_time' => 'datetime',
        'sort_order' => 'integer',
        'status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * 查询作用域：启用状态
     */
    public function scopeEnabled($query)
    {
        return $query->where('status', self::STATUS_ENABLED);
    }

    /**
     * 查询作用域：可领取
     */
    public function scopeReceivable($query)
    {
        $now = now();
        return $query->enabled()
            ->where('receive_type', self::RECEIVE_TYPE_PUBLIC)
            ->where(function ($q) use ($now) {
                $q->whereNull('receive_start_time')
                  ->orWhere('receive_start_time', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('receive_end_time')
                  ->orWhere('receive_end_time', '>=', $now);
            })
            ->where(function ($q) {
                $q->where('total_count', 0)
                  ->orWhereRaw('issued_count < total_count');
            });
    }

    /**
     * 是否可领取
     */
    public function canReceive(): bool
    {
        if ($this->status !== self::STATUS_ENABLED) {
            return false;
        }

        if ($this->total_count > 0 && $this->issued_count >= $this->total_count) {
            return false;
        }

        $now = now();
        if ($this->receive_start_time && $this->receive_start_time > $now) {
            return false;
        }

        if ($this->receive_end_time && $this->receive_end_time < $now) {
            return false;
        }

        return true;
    }

    /**
     * 计算优惠金额
     */
    public function calculateDiscount(float $amount): float
    {
        if ($amount < $this->threshold_amount) {
            return 0;
        }

        if ($this->coupon_type === self::TYPE_FULL_REDUCE || $this->coupon_type === self::TYPE_NO_THRESHOLD) {
            return min($this->discount_amount, $amount);
        }

        if ($this->coupon_type === self::TYPE_DISCOUNT) {
            $discount = $amount * (1 - $this->discount_rate);
            if ($this->max_discount > 0) {
                $discount = min($discount, $this->max_discount);
            }
            return $discount;
        }

        return 0;
    }

    /**
     * 检查是否适用于课程
     */
    public function isApplicable(int $courseId, ?int $categoryId = null): bool
    {
        if ($this->scope_type === self::SCOPE_ALL) {
            return true;
        }

        if ($this->scope_type === self::SCOPE_COURSE) {
            return in_array($courseId, $this->scope_ids ?? []);
        }

        if ($this->scope_type === self::SCOPE_CATEGORY && $categoryId) {
            return in_array($categoryId, $this->scope_ids ?? []);
        }

        return false;
    }
}
