<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppCourseTeacher extends Model
{
    use HasFactory;

    protected $table = 'app_course_teacher';
    protected $primaryKey = 'teacher_id';
    public $timestamps = false;

    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 2;

    const DEL_FLAG_NORMAL = 0;
    const DEL_FLAG_DELETED = 1;

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
        'create_by',
        'create_time',
        'update_by',
        'update_time',
        'del_flag',
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
        'del_flag' => 'integer',
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
    public function courses()
    {
        return $this->hasMany(AppCourseBase::class, 'teacher_id', 'teacher_id');
    }

    /**
     * 查询作用域：启用状态
     */
    public function scopeEnabled($query)
    {
        return $query->where('status', self::STATUS_ENABLED)
                     ->where('del_flag', self::DEL_FLAG_NORMAL);
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
        $this->update_time = now();
        $this->save();
    }
}
