<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppMemberPointLog extends Model
{
    use HasFactory;

    protected $table = 'app_member_point_log';
    protected $primaryKey = 'log_id';
    public $timestamps = false;

    // 变动类型
    const CHANGE_TYPE_EARN = 1;      // 获取
    const CHANGE_TYPE_USE = 2;       // 消费
    const CHANGE_TYPE_FREEZE = 3;    // 冻结
    const CHANGE_TYPE_UNFREEZE = 4;  // 解冻
    const CHANGE_TYPE_EXPIRE = 5;    // 过期
    const CHANGE_TYPE_ADJUST = 6;    // 后台调整

    // 来源类型
    const SOURCE_TYPE_TASK = 1;       // 任务奖励
    const SOURCE_TYPE_CONSUME = 2;    // 消费抵扣
    const SOURCE_TYPE_REFUND = 3;     // 订单退款
    const SOURCE_TYPE_GIFT = 4;       // 后台赠送
    const SOURCE_TYPE_DEDUCT = 5;     // 后台扣除
    const SOURCE_TYPE_EXPIRE = 6;     // 过期清零
    const SOURCE_TYPE_ACTIVITY = 7;   // 活动奖励

    protected $fillable = [
        'member_id',
        'change_type',
        'change_value',
        'before_points',
        'after_points',
        'source_type',
        'source_id',
        'task_code',
        'order_no',
        'title',
        'remark',
        'operator_id',
        'operator_name',
        'expire_time',
        'client_ip',
        'create_time',
    ];

    protected $casts = [
        'log_id' => 'integer',
        'member_id' => 'integer',
        'change_type' => 'integer',
        'change_value' => 'integer',
        'before_points' => 'integer',
        'after_points' => 'integer',
        'source_type' => 'integer',
        'operator_id' => 'integer',
        'expire_time' => 'datetime',
        'create_time' => 'datetime',
    ];

    /**
     * 关联用户
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function member()
    {
        return $this->belongsTo(AppMemberBase::class, 'member_id', 'member_id');
    }

    /**
     * 查询作用域：按用户筛选
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $memberId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByMember($query, int $memberId)
    {
        return $query->where('member_id', $memberId);
    }

    /**
     * 查询作用域：按变动类型筛选
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $changeType
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByChangeType($query, int $changeType)
    {
        return $query->where('change_type', $changeType);
    }

    /**
     * 查询作用域：获取记录
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEarned($query)
    {
        return $query->where('change_type', self::CHANGE_TYPE_EARN);
    }

    /**
     * 查询作用域：消费记录
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUsed($query)
    {
        return $query->where('change_type', self::CHANGE_TYPE_USE);
    }

    /**
     * 创建积分日志
     *
     * @param array $data
     * @return self
     */
    public static function createLog(array $data): self
    {
        $data['create_time'] = now();
        return self::create($data);
    }

    /**
     * 获取变动类型文本
     *
     * @return string
     */
    public function getChangeTypeTextAttribute(): string
    {
        $map = [
            self::CHANGE_TYPE_EARN => '获取',
            self::CHANGE_TYPE_USE => '消费',
            self::CHANGE_TYPE_FREEZE => '冻结',
            self::CHANGE_TYPE_UNFREEZE => '解冻',
            self::CHANGE_TYPE_EXPIRE => '过期',
            self::CHANGE_TYPE_ADJUST => '调整',
        ];

        return $map[$this->change_type] ?? '未知';
    }

    /**
     * 获取来源类型文本
     *
     * @return string
     */
    public function getSourceTypeTextAttribute(): string
    {
        $map = [
            self::SOURCE_TYPE_TASK => '任务奖励',
            self::SOURCE_TYPE_CONSUME => '消费抵扣',
            self::SOURCE_TYPE_REFUND => '订单退款',
            self::SOURCE_TYPE_GIFT => '后台赠送',
            self::SOURCE_TYPE_DEDUCT => '后台扣除',
            self::SOURCE_TYPE_EXPIRE => '过期清零',
            self::SOURCE_TYPE_ACTIVITY => '活动奖励',
        ];

        return $map[$this->source_type] ?? '未知';
    }
}
