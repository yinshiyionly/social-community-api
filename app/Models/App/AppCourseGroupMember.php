<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppCourseGroupMember extends Model
{
    use HasFactory;

    protected $table = 'app_course_group_member';
    protected $primaryKey = 'id';
    public $timestamps = false;

    // 状态
    const STATUS_PENDING = 0;    // 待支付
    const STATUS_PAID = 1;       // 已支付
    const STATUS_REFUNDED = 2;   // 已退款

    protected $fillable = [
        'group_id',
        'member_id',
        'order_no',
        'is_leader',
        'status',
        'join_time',
        'create_time',
    ];

    protected $casts = [
        'id' => 'integer',
        'group_id' => 'integer',
        'member_id' => 'integer',
        'is_leader' => 'integer',
        'status' => 'integer',
        'join_time' => 'datetime',
        'create_time' => 'datetime',
    ];

    /**
     * 关联拼团
     */
    public function group()
    {
        return $this->belongsTo(AppCourseGroup::class, 'group_id', 'group_id');
    }

    /**
     * 关联用户
     */
    public function member()
    {
        return $this->belongsTo(AppMemberBase::class, 'member_id', 'member_id');
    }

    /**
     * 关联订单
     */
    public function order()
    {
        return $this->belongsTo(AppCourseOrder::class, 'order_no', 'order_no');
    }

    /**
     * 是否团长
     */
    public function isLeader(): bool
    {
        return $this->is_leader === 1;
    }

    /**
     * 标记已支付
     */
    public function markPaid(): bool
    {
        $this->status = self::STATUS_PAID;
        $this->join_time = now();
        return $this->save();
    }
}
