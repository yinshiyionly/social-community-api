<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 课程列表资源 - 用于列表展示
 */
class CourseListResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'courseId' => $this->course_id,
            'courseNo' => $this->course_no,
            'categoryId' => $this->category_id,
            'categoryName' => $this->whenLoaded('category', function () {
                return $this->category ? $this->category->category_name : null;
            }),
            'courseTitle' => $this->course_title,
            'payType' => $this->pay_type,
            'playType' => $this->play_type,
            'coverImage' => $this->cover_image,
            'originalPrice' => $this->original_price,
            'currentPrice' => $this->current_price,
            'isFree' => $this->is_free,
            'totalChapter' => $this->total_chapter,
            'enrollCount' => $this->enroll_count,
            'viewCount' => $this->view_count,
            'sortOrder' => $this->sort_order,
            'isRecommend' => $this->is_recommend,
            'isHot' => $this->is_hot,
            'isNew' => $this->is_new,
            'status' => $this->status,
            'publishTime' => $this->publish_time ? $this->publish_time->format('Y-m-d H:i:s') : null,
            'createdAt' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
