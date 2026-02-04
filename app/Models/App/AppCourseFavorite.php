<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppCourseFavorite extends Model
{
    use HasFactory;

    protected $table = 'app_course_favorite';
    protected $primaryKey = 'id';
    public $timestamps = false;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'member_id',
        'course_id',
    ];

    protected $casts = [
        'id' => 'integer',
        'member_id' => 'integer',
        'course_id' => 'integer',
        'created_at' => 'datetime',
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
    public function course()
    {
        return $this->belongsTo(AppCourseBase::class, 'course_id', 'course_id');
    }

    /**
     * 查询作用域：按用户
     */
    public function scopeByMember($query, int $memberId)
    {
        return $query->where('member_id', $memberId);
    }

    /**
     * 检查是否已收藏
     */
    public static function isFavorited(int $memberId, int $courseId): bool
    {
        return self::byMember($memberId)
            ->where('course_id', $courseId)
            ->exists();
    }

    /**
     * 切换收藏状态
     */
    public static function toggle(int $memberId, int $courseId): bool
    {
        $favorite = self::byMember($memberId)
            ->where('course_id', $courseId)
            ->first();

        if ($favorite) {
            $favorite->delete();
            return false;
        }

        self::create([
            'member_id' => $memberId,
            'course_id' => $courseId,
        ]);

        return true;
    }
}
