<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Model;

/**
 * 帖子话题关联表
 */
class AppPostTopic extends Model
{
    protected $table = 'app_post_topic';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'post_id',
        'topic_id',
    ];

    protected $casts = [
        'id' => 'integer',
        'post_id' => 'integer',
        'topic_id' => 'integer',
    ];

    /**
     * 关联帖子
     */
    public function post()
    {
        return $this->belongsTo(AppPostBase::class, 'post_id', 'post_id');
    }

    /**
     * 关联话题
     */
    public function topic()
    {
        return $this->belongsTo(AppTopicBase::class, 'topic_id', 'topic_id');
    }

    /**
     * 查询作用域 - 按帖子筛选
     */
    public function scopeByPost($query, int $postId)
    {
        return $query->where('post_id', $postId);
    }

    /**
     * 查询作用域 - 按话题筛选
     */
    public function scopeByTopic($query, int $topicId)
    {
        return $query->where('topic_id', $topicId);
    }
}
