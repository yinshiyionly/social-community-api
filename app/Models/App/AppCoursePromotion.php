<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppCoursePromotion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'app_course_promotion';
    protected $primaryKey = 'promotion_id';

    // 倒计时类型
    const COUNTDOWN_TYPE_FIXED = 1;    // 固定结束时间
    const COUNTDOWN_TYPE_VISIT = 2;    // 访问后N小时

    protected $fillable = [
        'course_id',
        'landing_page_html',
        'landing_page_sections',
        'landing_page_bg',
        'landing_page_theme',
        'seckill_enabled',
        'seckill_price',
        'seckill_start_time',
        'seckill_end_time',
        'seckill_stock',
        'seckill_sold',
        'countdown_enabled',
        'countdown_type',
        'countdown_end_time',
        'countdown_hours',
        'countdown_text',
        'fake_data_enabled',
        'fake_enroll_base',
        'fake_enroll_increment',
        'fake_view_base',
        'fake_view_increment',
        'fake_recent_buyers',
        'discount_enabled',
        'discount_price',
        'discount_start_time',
        'discount_end_time',
        'discount_label',
        'group_buy_enabled',
        'group_buy_price',
        'group_buy_size',
        'group_buy_hours',
        'distribute_enabled',
        'distribute_ratio',
        'distribute_amount',
        'coupon_ids',
    ];

    protected $casts = [
        'promotion_id' => 'integer',
        'course_id' => 'integer',
        'landing_page_sections' => 'array',
        'seckill_enabled' => 'integer',
        'seckill_price' => 'decimal:2',
        'seckill_start_time' => 'datetime',
        'seckill_end_time' => 'datetime',
        'seckill_stock' => 'integer',
        'seckill_sold' => 'integer',
        'countdown_enabled' => 'integer',
        'countdown_type' => 'integer',
        'countdown_end_time' => 'datetime',
        'countdown_hours' => 'integer',
        'fake_data_enabled' => 'integer',
        'fake_enroll_base' => 'integer',
        'fake_enroll_increment' => 'integer',
        'fake_view_base' => 'integer',
        'fake_view_increment' => 'integer',
        'fake_recent_buyers' => 'array',
        'discount_enabled' => 'integer',
        'discount_price' => 'decimal:2',
        'discount_start_time' => 'datetime',
        'discount_end_time' => 'datetime',
        'group_buy_enabled' => 'integer',
        'group_buy_price' => 'decimal:2',
        'group_buy_size' => 'integer',
        'group_buy_hours' => 'integer',
        'distribute_enabled' => 'integer',
        'distribute_ratio' => 'decimal:2',
        'distribute_amount' => 'decimal:2',
        'coupon_ids' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 关联课程
     */
    public function course()
    {
        return $this->belongsTo(AppCourseBase::class, 'course_id', 'course_id');
    }

    /**
     * 秒杀是否进行中
     */
    public function isSeckillActive(): bool
    {
        if (!$this->seckill_enabled) {
            return false;
        }

        $now = now();
        return $this->seckill_start_time <= $now 
            && $this->seckill_end_time >= $now
            && $this->seckill_sold < $this->seckill_stock;
    }

    /**
     * 限时优惠是否进行中
     */
    public function isDiscountActive(): bool
    {
        if (!$this->discount_enabled) {
            return false;
        }

        $now = now();
        return $this->discount_start_time <= $now && $this->discount_end_time >= $now;
    }

    /**
     * 获取当前有效价格
     */
    public function getEffectivePrice(): string
    {
        if ($this->isSeckillActive()) {
            return $this->seckill_price;
        }

        if ($this->isDiscountActive()) {
            return $this->discount_price;
        }

        return $this->course->current_price;
    }

    /**
     * 获取虚假报名数（含随机增量）
     */
    public function getFakeEnrollCount(): int
    {
        if (!$this->fake_data_enabled) {
            return 0;
        }

        $daysSinceCreate = now()->diffInDays($this->created_at);
        $randomIncrement = $daysSinceCreate * mt_rand(0, $this->fake_enroll_increment);

        return $this->fake_enroll_base + $randomIncrement;
    }
}
