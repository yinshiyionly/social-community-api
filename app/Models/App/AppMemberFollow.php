<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 用户关注关系表
 */
class AppMemberFollow extends Model
{
    use HasFactory;

    protected $table = 'app_member_follow';

    protected $primaryKey = 'follow_id';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'member_id',
        'follow_member_id',
        'source',
        'status',
    ];

    protected $casts = [
        'follow_id' => 'integer',
        'member_id' => 'integer',
        'follow_member_id' => 'integer',
        'status' => 'integer',
    ];

    // 状态常量
    const STATUS_NORMAL = 1;
    const STATUS_CANCELLED = 2;

    // 来源常量
    const SOURCE_SEARCH = 'search';
    const SOURCE_RECOMMEND = 'recommend';
    const SOURCE_PROFILE = 'profile';
    const SOURCE_QR = 'qr';
    const SOURCE_POST = 'post';
    const SOURCE_COMMENT = 'comment';

    /**
     * 关联关注者
     */
    public function member()
    {
        return $this->belongsTo(AppMemberBase::class, 'member_id', 'member_id');
    }

    /**
     * 关联被关注者
     */
    public function followMember()
    {
        return $this->belongsTo(AppMemberBase::class, 'follow_member_id', 'member_id');
    }

    /**
     * 查询作用域 - 正常状态
     */
    public function scopeNormal($query)
    {
        return $query->where('status', self::STATUS_NORMAL);
    }

    /**
     * 查询作用域 - 按关注者查询
     */
    public function scopeByMember($query, int $memberId)
    {
        return $query->where('member_id', $memberId);
    }

    /**
     * 查询作用域 - 按被关注者查询
     */
    public function scopeByFollowMember($query, int $followMemberId)
    {
        return $query->where('follow_member_id', $followMemberId);
    }

    /**
     * 判断是否正常关注状态
     */
    public function isNormal(): bool
    {
        return $this->status === self::STATUS_NORMAL;
    }
}
