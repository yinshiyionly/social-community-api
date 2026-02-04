<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 课程分类简单资源 - 用于下拉选项
 */
class CourseCategorySimpleResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'categoryId' => $this->category_id,
            'parentId' => $this->parent_id,
            'categoryName' => $this->category_name,
        ];
    }
}
