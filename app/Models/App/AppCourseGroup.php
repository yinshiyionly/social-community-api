<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppCourseGroup extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'app_course_group';
    protected $primaryKey = 'group_id';

    // 拼团状态
    const STATUS_GROUPING = 0;    // 拼团中
    const STATUS_SUCCESS = 1;     // 已成团
    const STATUS_FAILED = 2;      // 已失败
    const STATUS_CANCELLED = 3;   // 已取消

    protected $fillable = [
        'course_id',
        'leader_id',
        'order_no',
        'group_size',
        'current_size',
        'group_price',
        'status',
        'expire_time',
        'success_time',
    ];

    protected $casts = [
        'group_id' => 'integer',
        'course_id' => 'integer',
        'leader_id' => 'integer',
        'group_size' => 'integer',
        'current_size' => 'integer',
        'group_price' => 'decimal:2',
        'status' => 'integer',
        'expire_time' => 'datetime',
        'success_time' => 'datetime',
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
     * 关联团长
     */
    public function leader()
    {
        return $this->belongsTo(AppMemberBase::class, 'leader_id', 'member_id');
    }

    /**
     * 关联成员
     */
    public function members()
    {
        return $this->hasMany(AppCourseGroupMember::class, 'group_id', 'group_id');
    }

    /**
     * 查询作用域：拼团中
     */
    public function scopeGrouping($query)
    {
        return $query->where('status', self::STATUS_GROUPING);
    }

    /**
     * 是否拼团中
     */
    public function isGrouping(): bool
    {
        return $this->status === self::STATUS_GROUPING;
    }

    /**
     * 是否已成团
     */
    public function isSuccess(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    /**
     * 是否已过期
     */
    public function isExpired(): bool
    {
        return $this->expire_time && $this->expire_time < now();
    }

    /**
     * 检查是否可加入
     */
    public function canJoin(): bool
    {
        return $this->isGrouping() 
            && !$this->isExpired() 
            && $this->current_size < $this->group_size;
    }

    /**
     * 增加成员
     */
    public function addMember(): void
    {
        $this->current_size++;

        if ($this->current_size >= $this->group_size) {
            $this->status = self::STATUS_SUCCESS;
            $this->success_time = now();
        }

        $this->save();
    }

    /**
     * 标记失败
     */
    public function markFailed(): bool
    {
        $this->status = self::STATUS_FAILED;
        return $this->save();
    }
}
