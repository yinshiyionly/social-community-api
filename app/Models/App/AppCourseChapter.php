<?php

namespace App\Models\App;

use App\Models\Traits\HasOperator;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 课程章节基础模型。
 *
 * 职责：
 * 1. 承载章节基础字段（标题、解锁规则、排课时间、状态）；
 * 2. 提供章节内容关联（录播/直播/图文/音频）；
 * 3. 提供管理端常量选项，避免接口层重复维护枚举。
 */
class AppCourseChapter extends Model
{
    use HasFactory, SoftDeletes, HasOperator;

    protected $table = 'app_course_chapter';
    protected $primaryKey = 'chapter_id';

    // 解锁类型
    const UNLOCK_TYPE_IMMEDIATE = 1;  // 立即解锁
    const UNLOCK_TYPE_DAYS = 2;       // 按天数解锁
    const UNLOCK_TYPE_DATE = 3;       // 按日期解锁

    // 状态
    const STATUS_DRAFT = 0;
    const STATUS_ONLINE = 1;
    const STATUS_OFFLINE = 2;

    // 是否免费
    const IS_FREE_NO = 0;
    const IS_FREE_YES = 1;

    protected $fillable = [
        'course_id',
        'chapter_no',
        'chapter_title',
        'chapter_subtitle',
        'cover_image',
        'brief',
        'is_free',
        'is_preview',
        'unlock_type',
        'unlock_days',
        'unlock_date',
        'unlock_time',
        'chapter_start_time',
        'chapter_end_time',
        'has_homework',
        'homework_required',
        'duration',
        'min_learn_time',
        'allow_skip',
        'allow_speed',
        'view_count',
        'complete_count',
        'homework_count',
        'sort_order',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'chapter_id' => 'integer',
        'course_id' => 'integer',
        'chapter_no' => 'integer',
        'is_free' => 'integer',
        'is_preview' => 'integer',
        'unlock_type' => 'integer',
        'unlock_days' => 'integer',
        'unlock_date' => 'date',
        'chapter_start_time' => 'datetime',
        'chapter_end_time' => 'datetime',
        'has_homework' => 'integer',
        'homework_required' => 'integer',
        'duration' => 'integer',
        'min_learn_time' => 'integer',
        'allow_skip' => 'integer',
        'allow_speed' => 'integer',
        'view_count' => 'integer',
        'complete_count' => 'integer',
        'homework_count' => 'integer',
        'sort_order' => 'integer',
        'status' => 'integer',
        'created_at' => 'datetime',
        'created_by' => 'integer',
        'updated_at' => 'datetime',
        'updated_by' => 'integer',
        'deleted_at' => 'datetime',
        'deleted_by' => 'integer',
    ];

    /**
     * 解锁类型文本映射。
     *
     * @return array<int, string>
     */
    public static function getUnlockTypeTextMap(): array
    {
        return [
            self::UNLOCK_TYPE_IMMEDIATE => '立即解锁',
            self::UNLOCK_TYPE_DAYS => '按天数解锁',
            self::UNLOCK_TYPE_DATE => '按日期解锁',
        ];
    }

    /**
     * 状态文本映射。
     *
     * @return array<int, string>
     */
    public static function getStatusTextMap(): array
    {
        return [
            self::STATUS_DRAFT => '草稿',
            self::STATUS_ONLINE => '上架',
            self::STATUS_OFFLINE => '下架',
        ];
    }

    /**
     * 是否免费文本映射。
     *
     * @return array<int, string>
     */
    public static function getIsFreeTextMap(): array
    {
        return [
            self::IS_FREE_YES => '免费',
            self::IS_FREE_NO => '付费',
        ];
    }

    /**
     * 获取解锁类型选项。
     *
     * @return array<int, array{label:string, value:int}>
     */
    public static function getUnlockTypeOptions(): array
    {
        return self::buildOptions(self::getUnlockTypeTextMap());
    }

    /**
     * 获取状态选项。
     *
     * @return array<int, array{label:string, value:int}>
     */
    public static function getStatusOptions(): array
    {
        return self::buildOptions(self::getStatusTextMap());
    }

    /**
     * 获取是否免费选项。
     *
     * @return array<int, array{label:string, value:int}>
     */
    public static function getIsFreeOptions(): array
    {
        return self::buildOptions(self::getIsFreeTextMap());
    }

    /**
     * 关联课程
     */
    public function course()
    {
        return $this->belongsTo(AppCourseBase::class, 'course_id', 'course_id');
    }

    /**
     * 关联作业
     */
    public function homeworks()
    {
        return $this->hasMany(AppChapterHomework::class, 'chapter_id', 'chapter_id');
    }

    /**
     * 关联图文内容
     */
    public function articleContent()
    {
        return $this->hasOne(AppChapterContentArticle::class, 'chapter_id', 'chapter_id');
    }

    /**
     * 关联视频内容
     */
    public function videoContent()
    {
        return $this->hasOne(AppChapterContentVideo::class, 'chapter_id', 'chapter_id');
    }

    /**
     * 关联直播内容
     */
    public function liveContent()
    {
        return $this->hasOne(AppChapterContentLive::class, 'chapter_id', 'chapter_id');
    }

    /**
     * 关联音频内容
     */
    public function audioContent()
    {
        return $this->hasOne(AppChapterContentAudio::class, 'chapter_id', 'chapter_id');
    }

    /**
     * 获取章节内容（根据课程类型）
     */
    public function getContent()
    {
        $course = $this->course;
        if (!$course) {
            return null;
        }

        switch ($course->play_type) {
            case AppCourseBase::PLAY_TYPE_ARTICLE:
                return $this->articleContent;
            case AppCourseBase::PLAY_TYPE_VIDEO:
                return $this->videoContent;
            case AppCourseBase::PLAY_TYPE_LIVE:
                return $this->liveContent;
            case AppCourseBase::PLAY_TYPE_AUDIO:
                return $this->audioContent;
            default:
                return null;
        }
    }

    /**
     * 查询作用域：上架状态
     */
    public function scopeOnline($query)
    {
        return $query->where('status', self::STATUS_ONLINE);
    }

    /**
     * 查询作用域：按课程
     */
    public function scopeByCourse($query, int $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    /**
     * 查询作用域：免费章节
     */
    public function scopeFree($query)
    {
        return $query->where('is_free', 1);
    }

    /**
     * 查询作用域：先导课
     */
    public function scopePreview($query)
    {
        return $query->where('is_preview', 1);
    }

    /**
     * 是否可免费观看
     */
    public function isFreeToWatch(): bool
    {
        return $this->is_free === 1 || $this->is_preview === 1;
    }

    /**
     * 计算解锁日期（动态解锁模式）
     */
    public function calculateUnlockDate(\DateTime $enrollDate): ?string
    {
        if ($this->unlock_type === self::UNLOCK_TYPE_IMMEDIATE) {
            return $enrollDate->format('Y-m-d');
        }

        if ($this->unlock_type === self::UNLOCK_TYPE_DAYS) {
            return (clone $enrollDate)->modify("+{$this->unlock_days} days")->format('Y-m-d');
        }

        if ($this->unlock_type === self::UNLOCK_TYPE_DATE) {
            return $this->unlock_date ? $this->unlock_date->format('Y-m-d') : null;
        }

        return null;
    }

    public function setChapterStartTimeAttribute($value)
    {
        $this->attributes['chapter_start_time'] = Carbon::make($value)->startOfMinute()->toDateTimeString();
    }

    public function setChapterEndTimeAttribute($value)
    {
        $this->attributes['chapter_end_time'] = Carbon::make($value)->startOfMinute()->toDateTimeString();
    }

    /**
     * 将 value=>label 映射转换为通用 options 结构。
     *
     * @param array<int, string> $map
     * @return array<int, array{label:string, value:int}>
     */
    private static function buildOptions(array $map): array
    {
        $options = [];
        foreach ($map as $value => $label) {
            $options[] = [
                'label' => $label,
                'value' => $value,
            ];
        }

        return $options;
    }
}
