<?php

namespace App\Http\Resources\System;

use Illuminate\Http\Resources\Json\JsonResource;

class DeptResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->dept_id ?? 0,
            'label' => $this->dept_name ?? '',
            'disabled' => ($this->status?? 1) ===1,
//            'deptId' => $this->dept_id,
//            'parentId' => $this->parent_id,
//            'ancestors' => $this->ancestors,
//            'deptName' => $this->dept_name,
//            'orderNum' => $this->order_num,
//            'leader' => $this->leader,
//            'phone' => $this->phone,
//            'email' => $this->email,
//            'status' => $this->status,
//            'delFlag' => $this->del_flag,
//            'createBy' => $this->create_by,
//            'createTime' => $this->create_time ? $this->create_time->format('Y-m-d H:i:s') : null,
//            'updateBy' => $this->update_by,
//            'updateTime' => $this->update_time ? $this->update_time->format('Y-m-d H:i:s') : null,
            'children' => $this->when(isset($this->children), function () {
                return DeptResource::collection($this->children);
            }),
        ];
    }
}
