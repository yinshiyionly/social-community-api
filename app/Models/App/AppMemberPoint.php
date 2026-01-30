<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppMemberPoint extends Model
{
    use HasFactory;

    protected $table = 'app_member_point';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'member_id',
        'total_points',
        'used_points',
        'available_points',
        'frozen_points',
        'expired_points',
        'level_points',
        'create_time',
        'update_time',
    ];

    protected $casts = [
        'id' => 'integer',
        'member_id' => 'integer',
        'total_points' => 'integer',
        'used_points' => 'integer',
        'available_points' => 'integer',
        'frozen_points' => 'integer',
        'expired_points' => 'integer',
        'level_points' => 'integer',
        'create_time' => 'datetime',
        'update_time' => 'datetime',
    ];

    /**
     * 关联用户
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function member()
    {
        return $this->belongsTo(AppMemberBase::class, 'member_id', 'member_id');
    }

    /**
     * 获取或创建用户积分账户
     *
     * @param int $memberId
     * @return self
     */
    public static function getOrCreate(int $memberId): self
    {
        $point = self::where('member_id', $memberId)->first();

        if (!$point) {
            $point = self::create([
                'member_id' => $memberId,
                'total_points' => 0,
                'used_points' => 0,
                'available_points' => 0,
                'frozen_points' => 0,
                'expired_points' => 0,
                'level_points' => 0,
                'create_time' => now(),
                'update_time' => now(),
            ]);
        }

        return $point;
    }

    /**
     * 增加积分
     *
     * @param int $points 积分数量
     * @param bool $addLevelPoints 是否同时增加等级积分
     * @return bool
     */
    public function addPoints(int $points, bool $addLevelPoints = true): bool
    {
        $this->total_points += $points;
        $this->available_points += $points;
        $this->update_time = now();

        if ($addLevelPoints) {
            $this->level_points += $points;
        }

        return $this->save();
    }

    /**
     * 消费积分
     *
     * @param int $points 积分数量
     * @return bool
     */
    public function usePoints(int $points): bool
    {
        if ($this->available_points < $points) {
            return false;
        }

        $this->used_points += $points;
        $this->available_points -= $points;
        $this->update_time = now();

        return $this->save();
    }

    /**
     * 冻结积分
     *
     * @param int $points 积分数量
     * @return bool
     */
    public function freezePoints(int $points): bool
    {
        if ($this->available_points < $points) {
            return false;
        }

        $this->available_points -= $points;
        $this->frozen_points += $points;
        $this->update_time = now();

        return $this->save();
    }

    /**
     * 解冻积分
     *
     * @param int $points 积分数量
     * @param bool $toAvailable 是否返还到可用积分
     * @return bool
     */
    public function unfreezePoints(int $points, bool $toAvailable = true): bool
    {
        if ($this->frozen_points < $points) {
            return false;
        }

        $this->frozen_points -= $points;
        $this->update_time = now();

        if ($toAvailable) {
            $this->available_points += $points;
        } else {
            $this->used_points += $points;
        }

        return $this->save();
    }

    /**
     * 检查积分是否足够
     *
     * @param int $points 需要的积分数量
     * @return bool
     */
    public function hasEnoughPoints(int $points): bool
    {
        return $this->available_points >= $points;
    }
}
