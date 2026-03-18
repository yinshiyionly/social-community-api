<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 课程列表资源 - 用于列表展示
 */
class CourseListResource extends JsonResource
{
    /**
     * 输出课程分页列表项。
     *
     * 字段约定：
     * 1. 返回字段统一使用 camelCase；
     * 2. teacherName/classTeacherName/classTeacherQr 直接映射课程主表字段；
     * 3. 时间字段统一格式化为 Y-m-d H:i:s。
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'courseId' => $this->course_id,
            // 'courseNo' => $this->course_no,
            'categoryId' => $this->category_id,
            'categoryName' => $this->whenLoaded('category', function () {
                return $this->category ? $this->category->category_name : null;
            }),
            'courseTitle' => $this->course_title,
            'payType' => $this->pay_type,
            'playType' => $this->play_type,
            'scheduleType' => $this->schedule_type,
            'teacherName' => $this->teacher_name,
            'classTeacherName' => $this->class_teacher_name,
            'classTeacherQr' => $this->class_teacher_qr,
            'coverImage' => $this->cover_image,
            'itemImage' => $this->item_image,
            'originalPrice' => $this->original_price,
            'currentPrice' => $this->current_price,
            'isFree' => $this->is_free,
            'status' => $this->status,
            'publishTime' => $this->publish_time ? $this->publish_time->format('Y-m-d H:i:s') : null,
            'createdAt' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
