<?php

namespace App\Http\Resources\System;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'userId' => $this->user_id,
            'deptId' => $this->dept_id,
            'userName' => $this->user_name,
            'nickName' => $this->nick_name,
            'userType' => $this->user_type,
            'email' => $this->email,
            'phonenumber' => $this->phonenumber,
            'sex' => $this->sex,
            'avatar' => $this->avatar,
            'status' => $this->status,
            'delFlag' => $this->del_flag,
            'loginIp' => $this->login_ip,
            'loginDate' => $this->login_date ? $this->login_date->toISOString() : null,
            'pwdUpdateDate' => $this->pwd_update_date ? $this->pwd_update_date->toISOString() : null,
            'createBy' => $this->create_by,
            'createTime' => $this->create_time ? $this->create_time->format('Y-m-d H:i:s') : null,
            'updateBy' => $this->update_by,
            'updateTime' => $this->update_time ? $this->update_time->format('Y-m-d H:i:s') : null,
            'remark' => $this->remark,
            // 是否同步添加教职人员
            'syncTechingUserFlag' => $this->sync_teching_user_flag ?? false,
            //
            'saleGroupId' => !empty($this->sale_group_id)
                ? $this->sale_group_id
                : null,
            'dept' => $this->whenLoaded('dept', function () {
                return new DeptResource($this->dept);
            }),
            'roles' => $this->whenLoaded('roles', function () {
                return RoleResource::collection($this->roles);
            }),
            'posts' => $this->whenLoaded('posts', function () {
                return PostResource::collection($this->posts);
            }),
            'roleIds' => $this->when(isset($this->roleIds), $this->roleIds),
            'postIds' => $this->when(isset($this->postIds), $this->postIds),
            'admin' => $this->isAdmin(),
            'password' => $this->password_plain ?? ''
        ];
    }
}
