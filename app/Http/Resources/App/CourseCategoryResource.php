<?php

namespace App\Http\Resources\App;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * App端课程分类资源
 */
class CourseCategoryResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->category_id,
            'name' => $this->category_name,
            'icon' => $this->icon,
        ];
    }
}
