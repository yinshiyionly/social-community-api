<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppMemberCoupon extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'app_member_coupon';
    protected $primaryKey = 'id';

    // 状态
    const STATUS_UNUSED = 0;     // 未使用
    const STATUS_USED = 1;       // 已使用
    const STATUS_EXPIRED = 2;    // 已过期

    protected $fillable = [
        'coupon_id',
        'member_id',
        'coupon_code',
        'status',
        'receive_time',
        'expire_time',
        'use_time',
        'use_order_no',
    ];

    protected $casts = [
        'id' => 'integer',
        'coupon_id' => 'integer',
        'member_id' => 'integer',
        'status' => 'integer',
        'receive_time' => 'datetime',
        'expire_time' => 'datetime',
        'use_time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 关联优惠券模板
     */
    public function template()
    {
        return $this->belongsTo(AppCouponTemplate::class, 'coupon_id', 'coupon_id');
    }

    /**
     * 关联用户
     */
    public function member()
    {
        return $this->belongsTo(AppMemberBase::class, 'member_id', 'member_id');
    }

    /**
     * 查询作用域：按用户
     */
    public function scopeByMember($query, int $memberId)
    {
        return $query->where('member_id', $memberId);
    }

    /**
     * 查询作用域：未使用
     */
    public function scopeUnused($query)
    {
        return $query->where('status', self::STATUS_UNUSED);
    }

    /**
     * 查询作用域：可用
     */
    public function scopeAvailable($query)
    {
        return $query->unused()
            ->where('expire_time', '>', now());
    }

    /**
     * 是否可用
     */
    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_UNUSED 
            && $this->expire_time > now();
    }

    /**
     * 使用优惠券
     */
    public function use(string $orderNo): bool
    {
        $this->status = self::STATUS_USED;
        $this->use_time = now();
        $this->use_order_no = $orderNo;
        return $this->save();
    }

    /**
     * 标记过期
     */
    public function markExpired(): bool
    {
        $this->status = self::STATUS_EXPIRED;
        return $this->save();
    }

    /**
     * 领取优惠券
     */
    public static function receive(int $memberId, AppCouponTemplate $template): ?self
    {
        // 检查是否可领取
        if (!$template->canReceive()) {
            return null;
        }

        // 检查用户领取数量
        $receivedCount = self::byMember($memberId)
            ->where('coupon_id', $template->coupon_id)
            ->count();

        if ($receivedCount >= $template->per_limit) {
            return null;
        }

        // 计算过期时间
        if ($template->valid_type === AppCouponTemplate::VALID_TYPE_FIXED) {
            $expireTime = $template->valid_end_time;
        } else {
            $expireTime = now()->addDays($template->valid_days);
        }

        // 创建用户优惠券
        $coupon = self::create([
            'coupon_id' => $template->coupon_id,
            'member_id' => $memberId,
            'status' => self::STATUS_UNUSED,
            'receive_time' => now(),
            'expire_time' => $expireTime,
        ]);

        // 更新已发放数量
        $template->increment('issued_count');

        return $coupon;
    }
}
