<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppChapterHomework extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'app_chapter_homework';
    protected $primaryKey = 'homework_id';

    // 作业类型
    const TYPE_IMAGE_TEXT = 1;   // 图文打卡
    const TYPE_VIDEO = 2;        // 视频打卡
    const TYPE_QA = 3;           // 问答
    const TYPE_FILE = 4;         // 文件提交

    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 2;

    protected $fillable = [
        'chapter_id',
        'course_id',
        'homework_title',
        'homework_content',
        'homework_type',
        'homework_config',
        'point_reward',
        'deadline_days',
        'need_review',
        'show_others',
        'submit_count',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'homework_id' => 'integer',
        'chapter_id' => 'integer',
        'course_id' => 'integer',
        'homework_type' => 'integer',
        'homework_config' => 'array',
        'point_reward' => 'integer',
        'deadline_days' => 'integer',
        'need_review' => 'integer',
        'show_others' => 'integer',
        'submit_count' => 'integer',
        'sort_order' => 'integer',
        'status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 关联章节
     */
    public function chapter()
    {
        return $this->belongsTo(AppCourseChapter::class, 'chapter_id', 'chapter_id');
    }

    /**
     * 关联课程
     */
    public function course()
    {
        return $this->belongsTo(AppCourseBase::class, 'course_id', 'course_id');
    }

    /**
     * 关联提交记录
     */
    public function submits()
    {
        return $this->hasMany(AppMemberHomeworkSubmit::class, 'homework_id', 'homework_id');
    }

    /**
     * 查询作用域：启用状态
     */
    public function scopeEnabled($query)
    {
        return $query->where('status', self::STATUS_ENABLED);
    }

    /**
     * 获取作业类型文本
     */
    public function getHomeworkTypeTextAttribute(): string
    {
        $map = [
            self::TYPE_IMAGE_TEXT => '图文打卡',
            self::TYPE_VIDEO => '视频打卡',
            self::TYPE_QA => '问答',
            self::TYPE_FILE => '文件提交',
        ];

        return $map[$this->homework_type] ?? '未知';
    }
}
