<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 帖子收藏表
 */
class AppPostCollection extends Model
{
    use HasFactory;

    protected $table = 'app_post_collection';

    protected $primaryKey = 'collection_id';

    protected $fillable = [
        'member_id',
        'post_id',
    ];

    protected $casts = [
        'collection_id' => 'integer',
        'member_id' => 'integer',
        'post_id' => 'integer',
    ];

    /**
     * 关联会员
     */
    public function member()
    {
        return $this->belongsTo(AppMemberBase::class, 'member_id', 'member_id');
    }

    /**
     * 关联帖子
     */
    public function post()
    {
        return $this->belongsTo(AppPostBase::class, 'post_id', 'post_id');
    }

    /**
     * 查询作用域 - 按会员筛选
     */
    public function scopeByMember($query, int $memberId)
    {
        return $query->where('member_id', $memberId);
    }

    /**
     * 查询作用域 - 按帖子筛选
     */
    public function scopeByPost($query, int $postId)
    {
        return $query->where('post_id', $postId);
    }

    /**
     * 检查是否已收藏
     *
     * @param int $memberId
     * @param int $postId
     * @return bool
     */
    public static function isCollected(int $memberId, int $postId): bool
    {
        return self::where('member_id', $memberId)
            ->where('post_id', $postId)
            ->exists();
    }

    /**
     * 批量检查是否已收藏
     *
     * @param int $memberId
     * @param array $postIds
     * @return array 已收藏的帖子ID数组
     */
    public static function getCollectedPostIds(int $memberId, array $postIds): array
    {
        if (empty($postIds)) {
            return [];
        }

        return self::where('member_id', $memberId)
            ->whereIn('post_id', $postIds)
            ->pluck('post_id')
            ->toArray();
    }
}
