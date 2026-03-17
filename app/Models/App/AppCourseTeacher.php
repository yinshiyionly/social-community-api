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
        'created_by' => 'integer',
        'updated_at' => 'datetime',
        'updated_by' => 'integer',
        'deleted_at' => 'datetime',
        'deleted_by' => 'integer',
    ];

    /**
     * 关联用户
     */
    public function member()
    {
        return $this->belongsTo(AppMemberBase::class, 'member_id', 'member_id');
    }

    /**
     * 关联课程（按讲师名称匹配）。
     *
     * 说明：
     * - 课程主表已从 teacher_id 切换为 teacher_name，本关联用于兼容存量讲师模块；
     * - 若 teacher_name 非唯一，统计结果会按同名聚合。
     */
    public function courses()
    {
        return $this->hasMany(AppCourseBase::class, 'teacher_name', 'teacher_name');
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
        // teacher_name 为空时，避免把所有 teacher_name 为空的课程误算到当前讲师。
        if (empty($this->teacher_name)) {
            $this->course_count = 0;
            $this->student_count = 0;
            $this->save();

            return;
        }

        $this->course_count = $this->courses()->online()->count();
        $this->student_count = AppMemberCourse::whereIn(
            'course_id',
            $this->courses()->online()->pluck('course_id')
        )->distinct('member_id')->count('member_id');
        $this->save();
    }
}
