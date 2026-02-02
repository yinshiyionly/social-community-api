<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 帖子评论点赞表
 */
class AppPostCommentLike extends Model
{
    use HasFactory;

    protected $table = 'app_post_comment_like';

    protected $primaryKey = 'like_id';

    protected $fillable = [
        'member_id',
        'comment_id',
    ];

    protected $casts = [
        'like_id' => 'integer',
        'member_id' => 'integer',
        'comment_id' => 'integer',
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
     * 关联评论
     */
    public function comment()
    {
        return $this->belongsTo(AppPostComment::class, 'comment_id', 'comment_id');
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
     * 按评论筛选
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $commentId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByComment($query, int $commentId)
    {
        return $query->where('comment_id', $commentId);
    }

    // ==================== 静态方法 ====================

    /**
     * 检查是否已点赞
     *
     * @param int $memberId
     * @param int $commentId
     * @return bool
     */
    public static function isLiked(int $memberId, int $commentId): bool
    {
        return self::byMember($memberId)
            ->byComment($commentId)
            ->exists();
    }

    /**
     * 批量检查是否已点赞
     *
     * @param int $memberId
     * @param array $commentIds
     * @return array 已点赞的评论ID数组
     */
    public static function getLikedCommentIds(int $memberId, array $commentIds): array
    {
        if (empty($commentIds)) {
            return [];
        }

        return self::byMember($memberId)
            ->whereIn('comment_id', $commentIds)
            ->pluck('comment_id')
            ->toArray();
    }
}
