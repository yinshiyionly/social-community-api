<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppCourseOrderPayLog extends Model
{
    use HasFactory;

    protected $table = 'app_course_order_pay_log';
    protected $primaryKey = 'log_id';
    public $timestamps = false;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    // 支付结果
    const PAY_RESULT_FAIL = 0;
    const PAY_RESULT_SUCCESS = 1;

    protected $fillable = [
        'order_no',
        'member_id',
        'pay_type',
        'pay_amount',
        'trade_no',
        'pay_result',
        'pay_response',
        'client_ip',
    ];

    protected $casts = [
        'log_id' => 'integer',
        'member_id' => 'integer',
        'pay_type' => 'integer',
        'pay_amount' => 'decimal:2',
        'pay_result' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * 关联订单
     */
    public function order()
    {
        return $this->belongsTo(AppCourseOrder::class, 'order_no', 'order_no');
    }

    /**
     * 创建支付日志
     */
    public static function createLog(array $data): self
    {
        return self::create($data);
    }
}
