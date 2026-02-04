<?php

namespace App\Models\App;

use App\Models\Traits\HasOperator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppCourseTeacher extends Model
{
    use HasFactory, SoftDeletes, HasOperator;

    protected $table = 'app_course_teacher';
    protected $primaryKey = 'teacher_id';

    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 2;

    protected $fillable = [
        'member_id',
        'teacher_name',
        'avatar',
        'title',
        'brief',
        'description',
        'tags',
        'certificates',
        'course_count',
        'student_count',
        'avg_rating',
        'sort_order',
        'is_recommend',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'teacher_id' => 'integer',
        'member_id' => 'integer',
        'tags' => 'array',
        'certificates' => 'array',
        'course_count' => 'integer',
        'student_count' => 'integer',
        'avg_rating' => 'decimal:1',
        'sort_order' => 'integer',
        'is_recommend' => 'integer',
        'status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
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
    public function courses()
    {
        return $this->hasMany(AppCourseBase::class, 'teacher_id', 'teacher_id');
    }

    /**
     * 查询作用域：启用状态
     */
    public function scopeEnabled($query)
    {
        return $query->where('status', self::STATUS_ENABLED);
    }

    /**
     * 查询作用域：推荐讲师
     */
    public function scopeRecommend($query)
    {
        return $query->where('is_recommend', 1);
    }

    /**
     * 更新课程统计
     */
    public function updateCourseStats(): void
    {
        $this->course_count = $this->courses()->online()->count();
        $this->student_count = AppMemberCourse::whereIn(
            'course_id',
            $this->courses()->online()->pluck('course_id')
        )->distinct('member_id')->count('member_id');
        $this->save();
    }
}
