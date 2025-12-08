<?php

namespace App\Models\System;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class SystemUser extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'sys_user';
    protected $primaryKey = 'user_id';
    public $timestamps = false;

    protected $fillable = [
        'dept_id', 'user_name', 'nick_name', 'user_type', 'email',
        'phonenumber', 'sex', 'avatar', 'password', 'password_plain', 'status', 'del_flag',
        'login_ip', 'login_date', 'pwd_update_date', 'create_by', 'create_time',
        'update_by', 'update_time', 'remark', 'sale_group_id', 'sync_teching_user_flag', 'force_change_password_flag'
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'create_time' => 'datetime',
        'update_time' => 'datetime',
        'login_date' => 'datetime',
        'pwd_update_date' => 'datetime',
        'sync_teching_user_flag' => 'boolean'
    ];

    // 关联部门
    public function dept()
    {
        return $this->belongsTo(SystemDept::class, 'dept_id', 'dept_id');
    }

    // 关联角色
    public function roles()
    {
        return $this->belongsToMany(SystemRole::class, 'sys_user_role', 'user_id', 'role_id');
    }

    // 关联岗位
    public function posts()
    {
        return $this->belongsToMany(SystemPost::class, 'sys_user_post', 'user_id', 'post_id');
    }

    // 获取用户权限
    public function getPermissions()
    {
        $permissions = [];
        foreach ($this->roles as $role) {
            foreach ($role->menus as $menu) {
                if ($menu->perms) {
                    $permissions[] = $menu->perms;
                }
            }
        }
        return array_unique($permissions);
    }

    // 获取用户角色标识
    public function getRoleKeys()
    {
        return $this->roles->pluck('role_key')->toArray();
    }

    // 检查是否为管理员
    public function isAdmin()
    {
        return $this->user_id == 1 || in_array('admin', $this->getRoleKeys());
    }

    // 获取数据权限范围
    public function getDataScope()
    {
        if ($this->isAdmin()) {
            return '1'; // 全部数据权限
        }

        $dataScopes = $this->roles->pluck('data_scope')->toArray();
        return min($dataScopes); // 取最小权限范围
    }
}
