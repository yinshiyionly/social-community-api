<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Model;

class SystemRole extends Model
{
    protected $table = 'sys_role';
    protected $primaryKey = 'role_id';
    public $timestamps = false;

    protected $fillable = [
        'role_name', 'role_key', 'role_sort', 'data_scope',
        'menu_check_strictly', 'dept_check_strictly', 'status',
        'del_flag', 'create_by', 'create_time', 'update_by',
        'update_time', 'remark'
    ];

    protected $casts = [
        'create_time' => 'datetime',
        'update_time' => 'datetime',
    ];

    // 关联用户
    public function users()
    {
        return $this->belongsToMany(SystemUser::class, 'sys_user_role', 'role_id', 'user_id');
    }

    // 关联菜单
    public function menus()
    {
        return $this->belongsToMany(SystemMenu::class, 'sys_role_menu', 'role_id', 'menu_id');
    }

    // 关联部门（数据权限）
    public function depts()
    {
        return $this->belongsToMany(SystemDept::class, 'sys_role_dept', 'role_id', 'dept_id');
    }

    // 获取角色菜单ID数组
    public function getMenuIds()
    {
        return $this->menus->pluck('menu_id')->toArray();
    }

    // 获取角色部门ID数组
    public function getDeptIds()
    {
        return $this->depts->pluck('dept_id')->toArray();
    }
}
