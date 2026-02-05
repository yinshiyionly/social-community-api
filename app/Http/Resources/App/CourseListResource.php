<?php

namespace App\Http\Resources\App;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * App端课程列表资源
 */
class CourseListResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->course_id,
            'cover' => $this->cover_image,
            'title' => $this->course_title,
            'desc' => $this->course_subtitle,
            'price' => $this->current_price,
            'originalPrice' => $this->original_price,
        ];
    }
}
