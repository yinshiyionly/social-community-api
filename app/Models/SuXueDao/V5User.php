<?php

namespace App\Models\SuXueDao;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 速学岛AI工具项目-用户表模型。
 */
class V5User extends Model
{
    use HasFactory;

    protected $connection = 'suxuedao';

    protected $table = 'v5_user';

    protected $primaryKey = 'id';

    protected $fillable = [
        'uid',
        'price_id',
        'mobile',
        'nickname',
        'avatar',
        'jifen',
        'status',
        'ip',
        'lastloginip',
        'lastlogintime',
        'created_at',
        'update_at',
        'open_at',
        'expire_at',
        'tg_code',
        'del_info',
        'tg_uid'
    ];
}
