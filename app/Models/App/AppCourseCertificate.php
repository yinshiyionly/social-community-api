<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppCourseCertificate extends Model
{
    use HasFactory;

    protected $table = 'app_course_certificate';
    protected $primaryKey = 'id';
    public $timestamps = false;

    // 发放条件
    const ISSUE_CONDITION_COMPLETE = 1;      // 完课即发
    const ISSUE_CONDITION_HOMEWORK = 2;      // 完课+作业
    const ISSUE_CONDITION_MANUAL = 3;        // 手动发放

    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 2;

    protected $fillable = [
        'course_id',
        'template_id',
        'certificate_title',
        'certificate_content',
        'issue_condition',
        'min_progress',
        'min_homework',
        'status',
        'create_time',
        'update_time',
    ];

    protected $casts = [
        'id' => 'integer',
        'course_id' => 'integer',
        'template_id' => 'integer',
        'issue_condition' => 'integer',
        'min_progress' => 'decimal:2',
        'min_homework' => 'integer',
        'status' => 'integer',
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
     * 关联模板
     */
    public function template()
    {
        return $this->belongsTo(AppCertificateTemplate::class, 'template_id', 'template_id');
    }

    /**
     * 检查用户是否满足发放条件
     */
    public function checkEligibility(AppMemberCourse $memberCourse): bool
    {
        if ($this->status !== self::STATUS_ENABLED) {
            return false;
        }

        // 检查进度
        if ($memberCourse->progress < $this->min_progress) {
            return false;
        }

        // 检查作业
        if ($this->issue_condition === self::ISSUE_CONDITION_HOMEWORK) {
            if ($memberCourse->homework_submitted < $this->min_homework) {
                return false;
            }
        }

        // 手动发放不自动检查
        if ($this->issue_condition === self::ISSUE_CONDITION_MANUAL) {
            return false;
        }

        return true;
    }
}
