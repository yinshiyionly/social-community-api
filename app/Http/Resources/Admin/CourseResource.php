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
            'courseNo' => $this->course_no,
            'categoryId' => $this->category_id,
            'courseTitle' => $this->course_title,
            'courseSubtitle' => $this->course_subtitle,
            'payType' => $this->pay_type,
            'playType' => $this->play_type,
            'scheduleType' => $this->schedule_type,
            'coverImage' => $this->cover_image,
            'coverVideo' => $this->cover_video,
            'bannerImages' => $this->banner_images,
            'introVideo' => $this->intro_video,
            'brief' => $this->brief,
            'description' => $this->description,
            'suitableCrowd' => $this->suitable_crowd,
            'learnGoal' => $this->learn_goal,
            'teacherId' => $this->teacher_id,
            'assistantIds' => $this->assistant_ids,
            'originalPrice' => $this->original_price,
            'currentPrice' => $this->current_price,
            'pointPrice' => $this->point_price,
            'isFree' => $this->is_free,
            'totalChapter' => $this->total_chapter,
            'totalDuration' => $this->total_duration,
            'validDays' => $this->valid_days,
            'allowDownload' => $this->allow_download,
            'allowComment' => $this->allow_comment,
            'allowShare' => $this->allow_share,
            'enrollCount' => $this->enroll_count,
            'viewCount' => $this->view_count,
            'completeCount' => $this->complete_count,
            'commentCount' => $this->comment_count,
            'avgRating' => $this->avg_rating,
            'sortOrder' => $this->sort_order,
            'isRecommend' => $this->is_recommend,
            'isHot' => $this->is_hot,
            'isNew' => $this->is_new,
            'status' => $this->status,
            'publishTime' => $this->publish_time ? $this->publish_time->format('Y-m-d H:i:s') : null,
            'createdAt' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updatedAt' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
            'category' => $this->whenLoaded('category', function () {
                return $this->category ? new CourseCategorySimpleResource($this->category) : null;
            }),
            'teacher' => $this->whenLoaded('teacher', function () {
                return $this->teacher ? new CourseTeacherSimpleResource($this->teacher) : null;
            }),
        ];
    }
}
