<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppCourseQa extends Model
{
    use HasFactory;

    protected $table = 'app_course_qa';
    protected $primaryKey = 'qa_id';
    public $timestamps = false;

    // 状态
    const STATUS_PENDING = 0;    // 待审核
    const STATUS_APPROVED = 1;   // 已通过
    const STATUS_REJECTED = 2;   // 已拒绝

    const DEL_FLAG_NORMAL = 0;
    const DEL_FLAG_DELETED = 1;

    protected $fillable = [
        'course_id',
        'chapter_id',
        'member_id',
        'parent_id',
        'content',
        'images',
        'is_teacher_reply',
        'like_count',
        'reply_count',
        'is_top',
        'is_excellent',
        'status',
        'create_time',
        'update_time',
        'del_flag',
    ];

    protected $casts = [
        'qa_id' => 'integer',
        'course_id' => 'integer',
        'chapter_id' => 'integer',
        'member_id' => 'integer',
        'parent_id' => 'integer',
        'images' => 'array',
        'is_teacher_reply' => 'integer',
        'like_count' => 'integer',
        'reply_count' => 'integer',
        'is_top' => 'integer',
        'is_excellent' => 'integer',
        'status' => 'integer',
        'del_flag' => 'integer',
        'create_time' => 'datetime',
        'update_time' => 'datetime',
    ];

    /**
     * 关联课程
     */
    public function course()
    {
        return $this->belongsTo(AppCourseBase::class, 'course_id', 'course_id');
    }

    /**
     * 关联章节
     */
    public function chapter()
    {
        return $this->belongsTo(AppCourseChapter::class, 'chapter_id', 'chapter_id');
    }

    /**
     * 关联用户
     */
    public function member()
    {
        return $this->belongsTo(AppMemberBase::class, 'member_id', 'member_id');
    }

    /**
     * 关联父级问答
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id', 'qa_id');
    }

    /**
     * 关联回复
     */
    public function replies()
    {
        return $this->hasMany(self::class, 'parent_id', 'qa_id');
    }

    /**
     * 查询作用域：已通过
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED)
                     ->where('del_flag', self::DEL_FLAG_NORMAL);
    }

    /**
     * 查询作用域：顶级问答（非回复）
     */
    public function scopeTopLevel($query)
    {
        return $query->where('parent_id', 0);
    }

    /**
     * 查询作用域：按课程
     */
    public function scopeByCourse($query, int $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    /**
     * 查询作用域：精选
     */
    public function scopeExcellent($query)
    {
        return $query->where('is_excellent', 1);
    }

    /**
     * 是否为讲师回复
     */
    public function isTeacherReply(): bool
    {
        return $this->is_teacher_reply === 1;
    }

    /**
     * 增加回复数
     */
    public function incrementReplyCount(): void
    {
        $this->increment('reply_count');
    }
}
