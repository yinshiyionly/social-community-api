<?php

namespace App\Http\Resources\System;

use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'roleId' => $this->role_id,
            'roleName' => $this->role_name,
            'roleKey' => $this->role_key,
            'roleSort' => $this->role_sort,
            'dataScope' => $this->data_scope,
            'menuCheckStrictly' => $this->menu_check_strictly == 1,
            'deptCheckStrictly' => $this->dept_check_strictly == 1,
            'status' => $this->status,
            'delFlag' => $this->del_flag,
            'createBy' => $this->create_by,
            'createTime' => $this->create_time ? $this->create_time->format('Y-m-d H:i:s') : null,
            'updateBy' => $this->update_by,
            'updateTime' => $this->update_time ? $this->update_time->format('Y-m-d H:i:s') : null,
            'remark' => $this->remark,
            'menuIds' => $this->when(isset($this->menuIds), $this->menuIds),
            'deptIds' => $this->when(isset($this->deptIds), $this->deptIds),
            'permissions' => $this->when(isset($this->permissions), $this->permissions),
            'admin' => $this->role_key === 'admin',
            'flag' => false,
        ];
    }
}
