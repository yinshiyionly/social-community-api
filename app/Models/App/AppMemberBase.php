<?php

namespace App\Models\App;

use App\Models\Traits\HasTosUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 会员基础信息表
 */
class AppMemberBase extends Model
{
    use HasFactory, SoftDeletes, HasTosUrl;

    protected $table = 'app_member_base';

    protected $primaryKey = 'member_id';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'phone',
        'email',
        'password',
        'nickname',
        'avatar',
        'gender',
        'birthday',
        'bio',
        'level',
        'points',
        'coin',
        'fans_count',
        'following_count',
        'like_count',
        'creation_count',
        'favorite_count',
        'invite_code',
        'inviter_id',
        'status',
        'is_official',
        'official_label',
    ];

    protected $casts = [
        'member_id' => 'integer',
        'gender' => 'integer',
        'birthday' => 'date',
        'level' => 'integer',
        'points' => 'integer',
        'coin' => 'integer',
        'fans_count' => 'integer',
        'following_count' => 'integer',
        'like_count' => 'integer',
        'creation_count' => 'integer',
        'favorite_count' => 'integer',
        'inviter_id' => 'integer',
        'status' => 'integer',
        'is_official' => 'integer',
    ];

    protected $hidden = [
        'password',
    ];

    // 性别常量
    const GENDER_UNKNOWN = 0;
    const GENDER_MALE = 1;
    const GENDER_FEMALE = 2;

    // 状态常量
    const STATUS_NORMAL = 1;
    const STATUS_DISABLED = 2;

    // 官方账号标识
    const OFFICIAL_NO = 0;
    const OFFICIAL_YES = 1;

    // 用户主页背景图片地址
    const MEMBER_BACKGROUND_IMAGE_URL = 'app/member-bg-image.png';

    // 小秘书官方账号ID
    const SECRETARY_MEMBER_ID = 1;

    /**
     * 关联第三方账号
     */
    public function oauthAccounts()
    {
        return $this->hasMany(AppMemberOauth::class, 'member_id', 'member_id');
    }

    /**
     * 关联邀请人
     */
    public function inviter()
    {
        return $this->belongsTo(self::class, 'inviter_id', 'member_id');
    }

    /**
     * 关联被邀请的会员
     */
    public function invitees()
    {
        return $this->hasMany(self::class, 'inviter_id', 'member_id');
    }

    /**
     * 查询作用域 - 正常状态
     */
    public function scopeNormal($query)
    {
        return $query->where('status', self::STATUS_NORMAL);
    }

    /**
     * 查询作用域 - 按手机号查询
     */
    public function scopeByPhone($query, string $phone)
    {
        return $query->where('phone', $phone);
    }

    /**
     * 查询作用域 - 按邀请码查询
     */
    public function scopeByInviteCode($query, string $code)
    {
        return $query->where('invite_code', $code);
    }

    /**
     * 判断账号是否正常
     */
    public function isNormal(): bool
    {
        return $this->status === self::STATUS_NORMAL;
    }

    /**
     * 判断账号是否被禁用
     */
    public function isDisabled(): bool
    {
        return $this->status === self::STATUS_DISABLED;
    }

    /**
     * 判断是否为官方账号
     */
    public function isOfficial(): bool
    {
        return $this->is_official === self::OFFICIAL_YES;
    }

    /**
     * 查询作用域 - 官方账号
     */
    public function scopeOfficial($query)
    {
        return $query->where('is_official', self::OFFICIAL_YES);
    }

    /**
     * 拼接 TOS URL 绝对路径
     *
     * @param $value
     * @return string|null
     */
    public function getAvatarAttribute($value)
    {
        return $this->getTosUrl($value);
    }

    /**
     * 用户主页背景图片
     * 拼接 TOS URL 绝对路径
     *
     * @return string|null
     */
    public function getBgImageAttribute()
    {
        return $this->getTosUrl(self::MEMBER_BACKGROUND_IMAGE_URL);
    }
}
