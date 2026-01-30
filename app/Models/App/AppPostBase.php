<?php

namespace App\Models\App;

use App\Models\Traits\HasTosUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 动态基础表
 *
 * @property int $post_id
 * @property int $member_id
 * @property int $post_type
 * @property array $media_data
 * @property array $location_geo
 * @property array $cover
 * @property string|null $post_ip
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class AppPostBase extends Model
{
    use HasFactory, SoftDeletes, HasTosUrl;

    protected $table = 'app_post_base';

    protected $primaryKey = 'post_id';

    protected $fillable = [
        'member_id',
        'post_type',
        'title',
        'content',
        'content_html',
        'media_data',
        'cover',
        'image_style',
        'post_ip',
        'location_name',
        'location_geo',
        'is_top',
        'sort_score',
        'visible',
        'status',
        'audit_msg',
    ];

    protected $casts = [
        'post_id' => 'integer',
        'member_id' => 'integer',
        'post_type' => 'integer',
        'media_data' => 'array',
        'cover' => 'array',
        'image_style' => 'integer',
        'location_geo' => 'array',
        'is_top' => 'integer',
        'sort_score' => 'float',
        'visible' => 'integer',
        'status' => 'integer',
    ];

    // 动态类型
    const POST_TYPE_IMAGE_TEXT = 1; // 图文动态
    const POST_TYPE_VIDEO = 2;      // 视频动态
    const POST_TYPE_ARTICLE = 3;    // 文章动态

    // 图片样式（图文动态）
    const IMAGE_STYLE_LARGE = 1;    // 大图
    const IMAGE_STYLE_PUZZLE = 2;   // 拼图

    // 可见性
    const VISIBLE_PUBLIC = 1;       // 公开
    const VISIBLE_PRIVATE = 0;      // 私密

    // 状态
    const STATUS_PENDING = 0;       // 待审核
    const STATUS_APPROVED = 1;      // 已通过
    const STATUS_REJECTED = 2;      // 已拒绝

    // 置顶
    const IS_TOP_NO = 0;
    const IS_TOP_YES = 1;

    /**
     * 查询作用域 - 已审核通过
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * 查询作用域 - 公开可见
     */
    public function scopeVisible($query)
    {
        return $query->where('visible', self::VISIBLE_PUBLIC);
    }

    /**
     * 查询作用域 - 按用户筛选
     */
    public function scopeByMember($query, int $memberId)
    {
        return $query->where('member_id', $memberId);
    }

    /**
     * 查询作用域 - 按类型筛选
     */
    public function scopeByType($query, int $postType)
    {
        return $query->where('post_type', $postType);
    }

    /**
     * 查询作用域 - 置顶优先排序
     */
    public function scopeOrderByTop($query)
    {
        return $query->orderByDesc('is_top')->orderByDesc('created_at');
    }

    /**
     * 关联统计数据
     */
    public function stat()
    {
        return $this->hasOne(AppPostStat::class, 'post_id', 'post_id');
    }

    /**
     * 获取或创建统计记录
     */
    public function getOrCreateStat(): AppPostStat
    {
        $stat = $this->stat;
        if (!$stat) {
            $stat = AppPostStat::create(['post_id' => $this->post_id]);
            $this->setRelation('stat', $stat);
        }
        return $stat;
    }

    /**
     * 关联作者（会员）
     */
    public function member()
    {
        return $this->belongsTo(AppMemberBase::class, 'member_id', 'member_id');
    }

    /**
     * 设置 media_data - 将绝对路径转为相对路径存储
     *
     * @param array|string|null $value
     */
    public function setMediaDataAttribute($value): void
    {
        if (empty($value)) {
            $this->attributes['media_data'] = '[]';
            return;
        }

        if (is_string($value)) {
            $value = json_decode($value, true) ?: [];
        }

        foreach ($value as &$item) {
            if (isset($item['url'])) {
                $item['url'] = $this->extractTosPath($item['url']);
            }
        }

        $this->attributes['media_data'] = json_encode($value);
    }

    /**
     * 获取 media_data - 将相对路径转为绝对路径
     *
     * @param string|null $value
     * @return array
     */
    public function getMediaDataAttribute($value): array
    {
        if (empty($value)) {
            return [];
        }

        $data = is_string($value) ? json_decode($value, true) : $value;
        if (!is_array($data)) {
            return [];
        }

        foreach ($data as &$item) {
            if (isset($item['url'])) {
                $item['url'] = $this->getTosUrl($item['url']);
            }
        }

        return $data;
    }

    /**
     * 设置 cover - 将绝对路径转为相对路径存储
     *
     * @param array|string|null $value
     */
    public function setCoverAttribute($value): void
    {
        if (empty($value)) {
            $this->attributes['cover'] = '{}';
            return;
        }

        if (is_string($value)) {
            $value = json_decode($value, true) ?: [];
        }

        if (isset($value['url'])) {
            $value['url'] = $this->extractTosPath($value['url']);
        }

        $this->attributes['cover'] = json_encode($value);
    }

    /**
     * 获取 cover - 将相对路径转为绝对路径
     *
     * @param string|null $value
     * @return array
     */
    public function getCoverAttribute($value): array
    {
        if (empty($value)) {
            return [];
        }

        $data = is_string($value) ? json_decode($value, true) : $value;
        if (!is_array($data)) {
            return [];
        }

        if (isset($data['url'])) {
            $data['url'] = $this->getTosUrl($data['url']);
        }

        return $data;
    }
}
