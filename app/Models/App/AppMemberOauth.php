<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 会员第三方账号关联表
 */
class AppMemberOauth extends Model
{
    use HasFactory;

    protected $table = 'app_member_oauth';

    protected $primaryKey = 'id';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'member_id',
        'platform',
        'openid',
        'unionid',
        'nickname',
        'avatar',
        'gender',
        'country',
        'province',
        'city',
        'raw_data',
        'access_token',
        'refresh_token',
        'token_expires_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'member_id' => 'integer',
        'gender' => 'integer',
        'raw_data' => 'array',
        'token_expires_at' => 'datetime',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    // 平台常量
    const PLATFORM_WECHAT_MP = 'wechat_mp';      // 微信小程序
    const PLATFORM_WECHAT_APP = 'wechat_app';    // 微信APP
    const PLATFORM_WECHAT_H5 = 'wechat_h5';      // 微信公众号/H5
    const PLATFORM_QQ = 'qq';                     // QQ
    const PLATFORM_APPLE = 'apple';              // Apple

    /**
     * 关联会员
     */
    public function member()
    {
        return $this->belongsTo(AppMemberBase::class, 'member_id', 'member_id');
    }

    /**
     * 查询作用域 - 按平台查询
     */
    public function scopeByPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    /**
     * 查询作用域 - 按 openid 查询
     */
    public function scopeByOpenid($query, string $openid)
    {
        return $query->where('openid', $openid);
    }

    /**
     * 查询作用域 - 按 unionid 查询
     */
    public function scopeByUnionid($query, string $unionid)
    {
        return $query->where('unionid', $unionid)->where('unionid', '!=', '');
    }

    /**
     * 查询作用域 - 按平台和 openid 查询
     */
    public function scopeByPlatformAndOpenid($query, string $platform, string $openid)
    {
        return $query->where('platform', $platform)->where('openid', $openid);
    }

    /**
     * 判断 token 是否过期
     */
    public function isTokenExpired(): bool
    {
        if (is_null($this->token_expires_at)) {
            return true;
        }
        return $this->token_expires_at->isPast();
    }

    /**
     * 判断是否为微信平台
     */
    public function isWechat(): bool
    {
        return in_array($this->platform, [
            self::PLATFORM_WECHAT_MP,
            self::PLATFORM_WECHAT_APP,
            self::PLATFORM_WECHAT_H5,
        ]);
    }
}
