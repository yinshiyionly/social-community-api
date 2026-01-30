<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Model;

/**
 * 动态统计表
 *
 * @property int $post_id
 * @property int $view_count
 * @property int $like_count
 * @property int $comment_count
 * @property int $share_count
 * @property int $collection_count
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class AppPostStat extends Model
{
    protected $table = 'app_post_stat';

    protected $primaryKey = 'post_id';

    public $incrementing = false;

    protected $fillable = [
        'post_id',
        'view_count',
        'like_count',
        'comment_count',
        'share_count',
        'collection_count',
    ];

    protected $casts = [
        'post_id' => 'integer',
        'view_count' => 'integer',
        'like_count' => 'integer',
        'comment_count' => 'integer',
        'share_count' => 'integer',
        'collection_count' => 'integer',
    ];

    /**
     * 关联动态
     */
    public function post()
    {
        return $this->belongsTo(AppPostBase::class, 'post_id', 'post_id');
    }

    /**
     * 增加浏览量
     */
    public function incrementViewCount(int $count = 1): bool
    {
        return $this->increment('view_count', $count);
    }

    /**
     * 增加点赞数
     */
    public function incrementLikeCount(int $count = 1): bool
    {
        return $this->increment('like_count', $count);
    }

    /**
     * 减少点赞数
     */
    public function decrementLikeCount(int $count = 1): bool
    {
        return $this->decrement('like_count', $count);
    }

    /**
     * 增加评论数
     */
    public function incrementCommentCount(int $count = 1): bool
    {
        return $this->increment('comment_count', $count);
    }

    /**
     * 减少评论数
     */
    public function decrementCommentCount(int $count = 1): bool
    {
        return $this->decrement('comment_count', $count);
    }

    /**
     * 增加分享数
     */
    public function incrementShareCount(int $count = 1): bool
    {
        return $this->increment('share_count', $count);
    }

    /**
     * 增加收藏数
     */
    public function incrementCollectionCount(int $count = 1): bool
    {
        return $this->increment('collection_count', $count);
    }

    /**
     * 减少收藏数
     */
    public function decrementCollectionCount(int $count = 1): bool
    {
        return $this->decrement('collection_count', $count);
    }
}
