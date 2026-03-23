<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * 用户积分账户表
 *
 * @property int $id
 * @property int $member_id
 * @property int $total_points
 * @property int $used_points
 * @property int $available_points
 * @property int $frozen_points
 * @property int $expired_points
 * @property int $level_points
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class AppMemberPoint extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'app_member_point';
    protected $primaryKey = 'id';

    protected $fillable = [
        'member_id',
        'total_points',
        'used_points',
        'available_points',
        'frozen_points',
        'expired_points',
        'level_points',
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
    ];

    /**
     * 模型事件注册。
     *
     * 设计约束：
     * 1. `app_member_base.points` 作为 App 展示镜像字段，必须与 `available_points` 保持一致；
     * 2. 任一同步失败都抛异常，让上层事务回滚，避免产生新的双表不一致。
     *
     * @return void
     */
    protected static function booted(): void
    {
        static::saved(function (self $point): void {
            $point->syncMemberBasePointsOrFail((int)$point->available_points);
        });

        static::restored(function (self $point): void {
            $point->syncMemberBasePointsOrFail((int)$point->available_points);
        });

        static::deleted(function (self $point): void {
            $point->syncMemberBasePointsOrFail(0);
        });
    }

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
            // 创建积分账户时用事务包裹，确保镜像同步失败可整体回滚。
            $point = DB::transaction(function () use ($memberId) {
                return self::create([
                    'member_id' => $memberId,
                    'total_points' => 0,
                    'used_points' => 0,
                    'available_points' => 0,
                    'frozen_points' => 0,
                    'expired_points' => 0,
                    'level_points' => 0,
                ]);
            });
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

    /**
     * 将可用积分同步到会员基础表镜像字段。
     *
     * 关键规则：
     * 1. 仅同步 `app_member_base.points`，不改动其他会员字段；
     * 2. 未命中会员记录或写入失败一律抛异常，由调用链决定回滚。
     *
     * @param int $points
     * @return void
     */
    public function syncMemberBasePointsOrFail(int $points): void
    {
        $affected = AppMemberBase::query()
            ->where('member_id', $this->member_id)
            ->update(['points' => $points]);

        if ($affected <= 0) {
            throw new \RuntimeException('同步会员积分镜像字段失败');
        }
    }
}
