<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 课程分类详情资源 - 用于详情/编辑页面
 */
class CourseCategoryResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'categoryId' => $this->category_id,
            'parentId' => $this->parent_id,
            'categoryName' => $this->category_name,
            'categoryCode' => $this->category_code,
            'icon' => $this->icon,
            'cover' => $this->cover,
            'description' => $this->description,
            'sortOrder' => $this->sort_order,
            'status' => $this->status,
            'createBy' => $this->create_by,
            'updateBy' => $this->update_by,
            'createTime' => $this->create_time ? $this->create_time->format('Y-m-d H:i:s') : null,
            'updateTime' => $this->update_time ? $this->update_time->format('Y-m-d H:i:s') : null,
            'parentName' => $this->whenLoaded('parent', function () {
                return $this->parent ? $this->parent->category_name : null;
            }),
        ];
    }
}
