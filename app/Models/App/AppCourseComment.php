<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppCourseComment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'app_course_comment';
    protected $primaryKey = 'comment_id';

    // 状态
    const STATUS_PENDING = 0;    // 待审核
    const STATUS_APPROVED = 1;   // 已通过
    const STATUS_REJECTED = 2;   // 已拒绝

    protected $fillable = [
        'course_id',
        'member_id',
        'order_id',
        'rating',
        'content',
        'images',
        'is_anonymous',
        'like_count',
        'is_top',
        'is_excellent',
        'status',
        'reply_content',
        'reply_time',
        'reply_by',
        'client_ip',
    ];

    protected $casts = [
        'comment_id' => 'integer',
        'course_id' => 'integer',
        'member_id' => 'integer',
        'order_id' => 'integer',
        'rating' => 'integer',
        'images' => 'array',
        'is_anonymous' => 'integer',
        'like_count' => 'integer',
        'is_top' => 'integer',
        'is_excellent' => 'integer',
        'status' => 'integer',
        'reply_time' => 'datetime',
        'reply_by' => 'integer',
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
     * 关联用户
     */
    public function member()
    {
        return $this->belongsTo(AppMemberBase::class, 'member_id', 'member_id');
    }

    /**
     * 查询作用域：已通过
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
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
     * 商家回复
     */
    public function reply(string $content, int $replyBy): bool
    {
        $this->reply_content = $content;
        $this->reply_by = $replyBy;
        $this->reply_time = now();
        return $this->save();
    }
}
