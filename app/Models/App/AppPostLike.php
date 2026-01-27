<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 帖子点赞表
 */
class AppPostLike extends Model
{
    use HasFactory;

    protected $table = 'app_post_like';

    protected $primaryKey = 'like_id';

    protected $fillable = [
        'member_id',
        'post_id',
    ];

    protected $casts = [
        'like_id' => 'integer',
        'member_id' => 'integer',
        'post_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==================== 关联关系 ====================

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

    // ==================== 查询作用域 ====================

    /**
     * 按会员筛选
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $memberId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByMember($query, int $memberId)
    {
        return $query->where('member_id', $memberId);
    }

    /**
     * 按帖子筛选
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $postId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByPost($query, int $postId)
    {
        return $query->where('post_id', $postId);
    }

    // ==================== 静态方法 ====================

    /**
     * 检查是否已点赞
     *
     * @param int $memberId
     * @param int $postId
     * @return bool
     */
    public static function isLiked(int $memberId, int $postId): bool
    {
        return self::byMember($memberId)
            ->byPost($postId)
            ->exists();
    }

    /**
     * 批量检查是否已点赞
     *
     * @param int $memberId
     * @param array $postIds
     * @return array 已点赞的帖子ID数组
     */
    public static function getLikedPostIds(int $memberId, array $postIds): array
    {
        if (empty($postIds)) {
            return [];
        }

        return self::byMember($memberId)
            ->whereIn('post_id', $postIds)
            ->pluck('post_id')
            ->toArray();
    }
}
