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
            'icon' => $this->icon,
            'sortOrder' => $this->sort_order,
            'status' => $this->status,
            'createBy' => $this->created_by,
            'updateBy' => $this->updated_by,
            'createTime' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updateTime' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
