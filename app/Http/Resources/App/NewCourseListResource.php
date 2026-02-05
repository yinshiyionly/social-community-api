<?php

namespace App\Http\Resources\App;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 好课上新列表资源
 */
class NewCourseListResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->course_id,
            'cover' => $this->cover_image,
            'title' => $this->course_title,
            'desc' => $this->teacher ? $this->teacher->brief : '',
            'price' => $this->current_price,
            'originalPrice' => $this->original_price,
        ];
    }
}
