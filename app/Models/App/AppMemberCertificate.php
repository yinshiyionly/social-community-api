<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppMemberCertificate extends Model
{
    use HasFactory;

    protected $table = 'app_member_certificate';
    protected $primaryKey = 'cert_id';
    public $timestamps = false;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    // 状态
    const STATUS_VALID = 1;      // 有效
    const STATUS_REVOKED = 2;    // 已撤销

    protected $fillable = [
        'cert_no',
        'member_id',
        'course_id',
        'template_id',
        'member_name',
        'course_title',
        'cert_image',
        'final_progress',
        'final_homework',
        'issue_time',
        'status',
    ];

    protected $casts = [
        'cert_id' => 'integer',
        'member_id' => 'integer',
        'course_id' => 'integer',
        'template_id' => 'integer',
        'final_progress' => 'decimal:2',
        'final_homework' => 'integer',
        'issue_time' => 'datetime',
        'status' => 'integer',
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
     * 关联模板
     */
    public function template()
    {
        return $this->belongsTo(AppCertificateTemplate::class, 'template_id', 'template_id');
    }

    /**
     * 查询作用域：按用户
     */
    public function scopeByMember($query, int $memberId)
    {
        return $query->where('member_id', $memberId);
    }

    /**
     * 查询作用域：有效
     */
    public function scopeValid($query)
    {
        return $query->where('status', self::STATUS_VALID);
    }

    /**
     * 检查用户是否已获得证书
     */
    public static function hasCertificate(int $memberId, int $courseId): bool
    {
        return self::byMember($memberId)
            ->where('course_id', $courseId)
            ->valid()
            ->exists();
    }

    /**
     * 生成证书编号
     */
    public static function generateCertNo(): string
    {
        return 'CERT' . date('YmdHis') . mt_rand(100000, 999999);
    }

    /**
     * 发放证书
     */
    public static function issue(
        int $memberId,
        int $courseId,
        AppCourseCertificate $config,
        AppMemberCourse $memberCourse,
        string $memberName
    ): ?self {
        // 检查是否已发放
        if (self::hasCertificate($memberId, $courseId)) {
            return null;
        }

        $course = AppCourseBase::find($courseId);
        if (!$course) {
            return null;
        }

        return self::create([
            'cert_no' => self::generateCertNo(),
            'member_id' => $memberId,
            'course_id' => $courseId,
            'template_id' => $config->template_id,
            'member_name' => $memberName,
            'course_title' => $course->course_title,
            'final_progress' => $memberCourse->progress,
            'final_homework' => $memberCourse->homework_submitted,
            'issue_time' => now(),
            'status' => self::STATUS_VALID,
        ]);
    }

    /**
     * 撤销证书
     */
    public function revoke(): bool
    {
        $this->status = self::STATUS_REVOKED;
        return $this->save();
    }
}
