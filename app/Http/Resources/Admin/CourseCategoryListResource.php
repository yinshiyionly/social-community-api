<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 课程分类列表资源 - 用于列表展示
 */
class CourseCategoryListResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'categoryId' => $this->category_id,
            // 'parentId' => $this->parent_id,
            'categoryName' => $this->category_name,
            'icon' => $this->icon,
            'sortOrder' => $this->sort_order,
            'status' => $this->status,
            'createdAt' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
