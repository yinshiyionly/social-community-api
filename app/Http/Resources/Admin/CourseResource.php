<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 课程详情资源 - 用于详情/编辑页面
 */
class CourseResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'courseId' => $this->course_id,
            // 'courseNo' => $this->course_no,
            'categoryId' => $this->category_id,
            'courseTitle' => $this->course_title,
            'courseSubtitle' => $this->course_subtitle,
            'payType' => $this->pay_type,
            'playType' => $this->play_type,
            'scheduleType' => $this->schedule_type,
            'coverImage' => $this->cover_image,
            'itemImage' => $this->item_image,
            'description' => $this->description,
            'remark' => $this->remark,
            'originalPrice' => $this->original_price,
            'currentPrice' => $this->current_price,
            'isFree' => $this->is_free,
            'status' => $this->status,
            'publishTime' => $this->publish_time ? $this->publish_time->format('Y-m-d H:i:s') : null,
            'createdAt' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updatedAt' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
            'category' => $this->whenLoaded('category', function () {
                return $this->category ? new CourseCategorySimpleResource($this->category) : null;
            }),
        ];
    }
}
