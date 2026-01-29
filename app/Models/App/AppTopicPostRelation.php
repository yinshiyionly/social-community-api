<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Model;

/**
 * 帖子话题关联表
 *
 * @property int $id
 * @property int $topic_id
 * @property int $post_id
 * @property int $member_id
 * @property int $is_featured
 * @property \Carbon\Carbon $created_at
 */
class AppTopicPostRelation extends Model
{
    protected $table = 'app_topic_post_relation';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'topic_id',
        'post_id',
        'member_id',
        'is_featured',
    ];

    protected $casts = [
        'id' => 'integer',
        'topic_id' => 'integer',
        'post_id' => 'integer',
        'member_id' => 'integer',
        'is_featured' => 'integer',
    ];

    // 是否精选
    const IS_FEATURED_NO = 0;
    const IS_FEATURED_YES = 1;

    /**
     * 查询作用域 - 精选帖子
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', self::IS_FEATURED_YES);
    }

    /**
     * 查询作用域 - 按话题筛选
     */
    public function scopeByTopic($query, int $topicId)
    {
        return $query->where('topic_id', $topicId);
    }

    /**
     * 查询作用域 - 按帖子筛选
     */
    public function scopeByPost($query, int $postId)
    {
        return $query->where('post_id', $postId);
    }

    /**
     * 查询作用域 - 按用户筛选
     */
    public function scopeByMember($query, int $memberId)
    {
        return $query->where('member_id', $memberId);
    }

    /**
     * 关联话题
     */
    public function topic()
    {
        return $this->belongsTo(AppTopicBase::class, 'topic_id', 'topic_id');
    }

    /**
     * 关联帖子
     */
    public function post()
    {
        return $this->belongsTo(AppPostBase::class, 'post_id', 'post_id');
    }

    /**
     * 关联发帖人
     */
    public function member()
    {
        return $this->belongsTo(AppMemberBase::class, 'member_id', 'member_id');
    }
}
