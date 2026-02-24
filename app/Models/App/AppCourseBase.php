<?php

namespace App\Models\App;

use App\Models\Traits\HasOperator;
use App\Models\Traits\HasTosUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppCourseBase extends Model
{
    use HasFactory, SoftDeletes, HasOperator, HasTosUrl;

    protected $table = 'app_course_base';
    protected $primaryKey = 'course_id';

    // 付费类型
    const PAY_TYPE_TRIAL = 1;      // 体验课
    const PAY_TYPE_BEGINNER = 2;   // 小白课
    const PAY_TYPE_ADVANCED = 3;   // 进阶课
    const PAY_TYPE_PAID = 4;       // 付费课

    // 付费类型展示配置
    const PAY_TYPE_CONFIG = [
        self::PAY_TYPE_TRIAL => [
            'typeName' => '招生0元课',
            'typeIcon' => '/static/svg/icon-free.svg',
            'typeIconColor' => '#4A90E2',
        ],
        self::PAY_TYPE_BEGINNER => [
            'typeName' => '小白课',
            'typeIcon' => '/static/svg/icon-beginner.svg',
            'typeIconColor' => '#F5A623',
        ],
        self::PAY_TYPE_ADVANCED => [
            'typeName' => '进阶课',
            'typeIcon' => '/static/svg/icon-advance.svg',
            'typeIconColor' => '#E62E2E',
        ],
        self::PAY_TYPE_PAID => [
            'typeName' => '付费课',
            'typeIcon' => '/static/svg/icon-paid.svg',
            'typeIconColor' => '#7B61FF',
        ],
    ];

    // 播放类型
    const PLAY_TYPE_ARTICLE = 1;   // 图文课
    const PLAY_TYPE_VIDEO = 2;     // 录播课
    const PLAY_TYPE_LIVE = 3;      // 直播课
    const PLAY_TYPE_AUDIO = 4;     // 音频课

    // 排课类型
    const SCHEDULE_TYPE_FIXED = 1;    // 固定日期
    const SCHEDULE_TYPE_DYNAMIC = 2;  // 动态解锁

    // 状态
    const STATUS_DRAFT = 0;     // 草稿
    const STATUS_ONLINE = 1;    // 上架
    const STATUS_OFFLINE = 2;   // 下架

    protected $fillable = [
        'course_no',
        'category_id',
        'course_title',
        'course_subtitle',
        'pay_type',
        'play_type',
        'schedule_type',
        'cover_image',
        'cover_video',
        'banner_images',
        'intro_video',
        'brief',
        'description',
        'suitable_crowd',
        'learn_goal',
        'teacher_id',
        'assistant_ids',
        'original_price',
        'current_price',
        'point_price',
        'is_free',
        'total_chapter',
        'total_duration',
        'valid_days',
        'allow_download',
        'allow_comment',
        'allow_share',
        'enroll_count',
        'view_count',
        'complete_count',
        'comment_count',
        'avg_rating',
        'sort_order',
        'is_recommend',
        'is_hot',
        'is_new',
        'status',
        'publish_time',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'course_id' => 'integer',
        'category_id' => 'integer',
        'pay_type' => 'integer',
        'play_type' => 'integer',
        'schedule_type' => 'integer',
        'banner_images' => 'array',
        'assistant_ids' => 'array',
        'original_price' => 'decimal:2',
        'current_price' => 'decimal:2',
        'point_price' => 'integer',
        'is_free' => 'integer',
        'total_chapter' => 'integer',
        'total_duration' => 'integer',
        'valid_days' => 'integer',
        'allow_download' => 'integer',
        'allow_comment' => 'integer',
        'allow_share' => 'integer',
        'enroll_count' => 'integer',
        'view_count' => 'integer',
        'complete_count' => 'integer',
        'comment_count' => 'integer',
        'avg_rating' => 'decimal:1',
        'sort_order' => 'integer',
        'is_recommend' => 'integer',
        'is_hot' => 'integer',
        'is_new' => 'integer',
        'status' => 'integer',
        'publish_time' => 'datetime',
        'created_at' => 'datetime',
        'created_by' => 'integer',
        'updated_at' => 'datetime',
        'updated_by' => 'integer',
        'deleted_at' => 'datetime',
        'deleted_by' => 'integer',
    ];


    /**
     * 关联分类
     */
    public function category()
    {
        return $this->belongsTo(AppCourseCategory::class, 'category_id', 'category_id');
    }

    /**
     * 关联讲师
     */
    public function teacher()
    {
        return $this->belongsTo(AppCourseTeacher::class, 'teacher_id', 'teacher_id');
    }

    /**
     * 关联推广配置
     */
    public function promotion()
    {
        return $this->hasOne(AppCoursePromotion::class, 'course_id', 'course_id');
    }

    /**
     * 关联章节
     */
    public function chapters()
    {
        return $this->hasMany(AppCourseChapter::class, 'course_id', 'course_id');
    }

    /**
     * 关联证书配置
     */
    public function certificate()
    {
        return $this->hasOne(AppCourseCertificate::class, 'course_id', 'course_id');
    }

    /**
     * 查询作用域：上架状态
     */
    public function scopeOnline($query)
    {
        return $query->where('status', self::STATUS_ONLINE);
    }

    /**
     * 查询作用域：按分类
     */
    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * 查询作用域：按付费类型
     */
    public function scopeByPayType($query, int $payType)
    {
        return $query->where('pay_type', $payType);
    }

    /**
     * 查询作用域：按播放类型
     */
    public function scopeByPlayType($query, int $playType)
    {
        return $query->where('play_type', $playType);
    }

    /**
     * 查询作用域：推荐课程
     */
    public function scopeRecommend($query)
    {
        return $query->where('is_recommend', 1);
    }

    /**
     * 查询作用域：免费课程
     */
    public function scopeFree($query)
    {
        return $query->where('is_free', 1);
    }

    /**
     * 是否为图文课
     */
    public function isArticleCourse(): bool
    {
        return $this->play_type === self::PLAY_TYPE_ARTICLE;
    }

    /**
     * 是否为录播课
     */
    public function isVideoCourse(): bool
    {
        return $this->play_type === self::PLAY_TYPE_VIDEO;
    }

    /**
     * 是否为直播课
     */
    public function isLiveCourse(): bool
    {
        return $this->play_type === self::PLAY_TYPE_LIVE;
    }

    /**
     * 是否为音频课
     */
    public function isAudioCourse(): bool
    {
        return $this->play_type === self::PLAY_TYPE_AUDIO;
    }

    /**
     * 是否为固定日期排课
     */
    public function isFixedSchedule(): bool
    {
        return $this->schedule_type === self::SCHEDULE_TYPE_FIXED;
    }

    /**
     * 是否为动态解锁排课
     */
    public function isDynamicSchedule(): bool
    {
        return $this->schedule_type === self::SCHEDULE_TYPE_DYNAMIC;
    }

    /**
     * 生成课程编号
     */
    public static function generateCourseNo(): string
    {
        return 'C' . date('YmdHis') . mt_rand(1000, 9999);
    }

    /**
     * 获取 cover - 将相对路径转为绝对路径
     *
     * @param string $value
     * @return string
     */
    public function getCoverImageAttribute($value): string
    {
        return $this->getTosUrl($value);
    }

    /**
     * 获取 banner_images - 将相对路径转为绝对路径
     *
     * @param string|null $value
     * @return array
     */
    public function getBannerImagesAttribute($value): array
    {
        $images = is_string($value) ? json_decode($value, true) : $value;

        if (empty($images) || !is_array($images)) {
            return [];
        }

        $result = [];
        foreach ($images as $image) {
            $result[] = $this->getTosUrl($image);
        }

        return $result;
    }

}
