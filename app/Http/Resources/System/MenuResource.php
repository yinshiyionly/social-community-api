<?php

namespace App\Http\Resources\System;

use Illuminate\Http\Resources\Json\JsonResource;

class MenuResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'menuId' => $this->menu_id,
            'menuName' => $this->menu_name,
            'parentId' => $this->parent_id,
            'orderNum' => $this->order_num,
            'path' => $this->path,
            'component' => $this->component,
            'query' => $this->query,
            'isFrame' => $this->is_frame,
            'isCache' => $this->is_cache,
            'menuType' => $this->menu_type,
            'visible' => $this->visible,
            'status' => $this->status,
            'perms' => $this->perms,
            'icon' => $this->icon,
            'createBy' => $this->create_by,
            'createTime' => $this->create_time ? $this->create_time->format('Y-m-d H:i:s') : null,
            'updateBy' => $this->update_by,
            'updateTime' => $this->update_time ? $this->update_time->format('Y-m-d H:i:s') : null,
            'remark' => $this->remark,
            'children' => $this->when(isset($this->children), function () {
                return MenuResource::collection($this->children);
            }),
        ];
    }
}
