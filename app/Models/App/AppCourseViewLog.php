<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppCourseViewLog extends Model
{
    use HasFactory;

    protected $table = 'app_course_view_log';
    protected $primaryKey = 'id';
    public $timestamps = false;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'member_id',
        'course_id',
        'device_id',
        'client_ip',
        'user_agent',
        'referer',
        'duration',
    ];

    protected $casts = [
        'id' => 'integer',
        'member_id' => 'integer',
        'course_id' => 'integer',
        'duration' => 'integer',
        'created_at' => 'datetime',
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
     * 记录浏览
     */
    public static function record(int $courseId, ?int $memberId = null, array $extra = []): self
    {
        return self::create(array_merge([
            'course_id' => $courseId,
            'member_id' => $memberId,
        ], $extra));
    }
}
