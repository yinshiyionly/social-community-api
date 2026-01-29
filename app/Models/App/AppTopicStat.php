<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Model;

/**
 * 话题统计表
 *
 * @property int $topic_id
 * @property int $post_count
 * @property int $view_count
 * @property int $follow_count
 * @property int $participant_count
 * @property int $today_post_count
 * @property float $heat_score
 * @property \Carbon\Carbon|null $last_post_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class AppTopicStat extends Model
{
    protected $table = 'app_topic_stat';

    protected $primaryKey = 'topic_id';

    public $incrementing = false;

    protected $fillable = [
        'topic_id',
        'post_count',
        'view_count',
        'follow_count',
        'participant_count',
        'today_post_count',
        'heat_score',
        'last_post_at',
    ];

    protected $casts = [
        'topic_id' => 'integer',
        'post_count' => 'integer',
        'view_count' => 'integer',
        'follow_count' => 'integer',
        'participant_count' => 'integer',
        'today_post_count' => 'integer',
        'heat_score' => 'float',
        'last_post_at' => 'datetime',
    ];

    /**
     * 查询作用域 - 按热度排序
     */
    public function scopeOrderByHeat($query)
    {
        return $query->orderByDesc('heat_score');
    }

    /**
     * 查询作用域 - 按帖子数排序
     */
    public function scopeOrderByPostCount($query)
    {
        return $query->orderByDesc('post_count');
    }

    /**
     * 关联话题基础信息
     */
    public function topic()
    {
        return $this->belongsTo(AppTopicBase::class, 'topic_id', 'topic_id');
    }

    /**
     * 增加帖子数
     */
    public function incrementPostCount(int $count = 1): bool
    {
        return $this->increment('post_count', $count);
    }

    /**
     * 减少帖子数
     */
    public function decrementPostCount(int $count = 1): bool
    {
        return $this->decrement('post_count', $count);
    }

    /**
     * 增加浏览数
     */
    public function incrementViewCount(int $count = 1): bool
    {
        return $this->increment('view_count', $count);
    }

    /**
     * 增加关注数
     */
    public function incrementFollowCount(int $count = 1): bool
    {
        return $this->increment('follow_count', $count);
    }

    /**
     * 减少关注数
     */
    public function decrementFollowCount(int $count = 1): bool
    {
        return $this->decrement('follow_count', $count);
    }

    /**
     * 增加参与人数
     */
    public function incrementParticipantCount(int $count = 1): bool
    {
        return $this->increment('participant_count', $count);
    }

    /**
     * 更新最后发帖时间
     */
    public function updateLastPostAt(): bool
    {
        return $this->update(['last_post_at' => now()]);
    }

    /**
     * 重置今日帖子数（定时任务调用）
     */
    public function resetTodayPostCount(): bool
    {
        return $this->update(['today_post_count' => 0]);
    }
}
