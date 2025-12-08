<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Model;

class SystemPost extends Model
{
    protected $table = 'sys_post';
    protected $primaryKey = 'post_id';
    public $timestamps = false;

    protected $fillable = [
        'post_code', 'post_name', 'post_sort', 'status',
        'create_by', 'create_time', 'update_by', 'update_time', 'remark'
    ];

    protected $casts = [
        'create_time' => 'datetime',
        'update_time' => 'datetime',
    ];

    // 关联用户
    public function users()
    {
        return $this->belongsToMany(SystemUser::class, 'sys_user_post', 'post_id', 'user_id');
    }
}
