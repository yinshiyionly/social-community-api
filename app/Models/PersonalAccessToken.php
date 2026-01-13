<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonalAccessToken extends Model
{
    protected $table = 'personal_access_tokens';
    protected $primaryKey = 'id';

    protected $fillable = [
        'tokenable_type',
        'tokenable_id',
        'name',
        'token',
        'abilities',
        'last_used_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'tokenable_id' => 'integer',
        'abilities' => 'array',
        'last_used_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [
        'token',
    ];

    /**
     * 多态关联 - 获取 token 所属的模型
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function tokenable()
    {
        return $this->morphTo();
    }

    /**
     * 根据 token 查找记录
     *
     * @param string $token
     * @return static|null
     */
    public static function findToken(string $token)
    {
        return static::where('token', hash('sha256', $token))->first();
    }

    /**
     * 检查是否具有指定能力
     *
     * @param string $ability
     * @return bool
     */
    public function can(string $ability): bool
    {
        $abilities = $this->abilities ?: [];

        return in_array('*', $abilities) || in_array($ability, $abilities);
    }

    /**
     * 检查是否不具有指定能力
     *
     * @param string $ability
     * @return bool
     */
    public function cant(string $ability): bool
    {
        return !$this->can($ability);
    }
}
