<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 直播间预约记录模型。
 *
 * 职责：
 * 1. 保存会员对直播间的预约状态；
 * 2. 通过 status 标识预约/取消，避免频繁物理删改；
 * 3. 为首页 isReserved 判定提供 member_id + room_id 幂等键。
 */
class AppLiveRoomReserve extends Model
{
    use HasFactory;

    protected $table = 'app_live_room_reserve';
    protected $primaryKey = 'reserve_id';

    // 预约状态
    const STATUS_RESERVED = 1;     // 正常预约
    const STATUS_CANCELLED = 2;    // 取消预约

    protected $fillable = [
        'member_id',
        'room_id',
        'status',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'reserve_id' => 'integer',
        'member_id' => 'integer',
        'room_id' => 'integer',
        'status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 关联直播间。
     */
    public function room()
    {
        return $this->belongsTo(AppLiveRoom::class, 'room_id', 'room_id');
    }
}
