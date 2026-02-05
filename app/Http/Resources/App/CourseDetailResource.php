<?php

namespace App\Http\Resources\App;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * App端课程详情资源
 */
class CourseDetailResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->course_id,
            'title' => $this->course_title,
            'subtitle' => $this->course_subtitle,
            'cover' => $this->cover_image,
            'coverVideo' => $this->cover_video,
            'bannerImages' => $this->banner_images,
            'introVideo' => $this->intro_video,
            'brief' => $this->brief,
            'description' => $this->description,
            'suitableCrowd' => $this->suitable_crowd,
            'learnGoal' => $this->learn_goal,
            'payType' => $this->pay_type,
            'playType' => $this->play_type,
            'price' => $this->current_price,
            'originalPrice' => $this->original_price,
            'pointPrice' => $this->point_price,
            'isFree' => $this->is_free,
            'totalChapter' => $this->total_chapter,
            'totalDuration' => $this->total_duration,
            'validDays' => $this->valid_days,
            'enrollCount' => $this->enroll_count,
            'viewCount' => $this->view_count,
            'avgRating' => $this->avg_rating,
            'allowComment' => $this->allow_comment,
            'allowShare' => $this->allow_share,
            'teacher' => $this->whenLoaded('teacher', function () {
                return [
                    'id' => $this->teacher->teacher_id,
                    'name' => $this->teacher->teacher_name,
                    'avatar' => $this->teacher->avatar,
                    'title' => $this->teacher->title,
                    'brief' => $this->teacher->brief,
                ];
            }),
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->category_id,
                    'name' => $this->category->category_name,
                ];
            }),
        ];
    }
}
