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
     * 检查是否已点赞
     *
     * @param int $memberId
     * @param int $postId
     * @return bool
     */
    public static function isLiked(int $memberId, int $postId): bool
    {
        return self::where('member_id', $memberId)
            ->where('post_id', $postId)
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

        return self::where('member_id', $memberId)
            ->whereIn('post_id', $postIds)
            ->pluck('post_id')
            ->toArray();
    }
}
