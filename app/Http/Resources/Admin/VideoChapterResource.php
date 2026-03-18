<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 录播课章节详情资源 - 用于详情/编辑页面
 */
class VideoChapterResource extends JsonResource
{
    /**
     * 输出录播章节详情。
     *
     * 字段约定：
     * 1. videoId 为单视频绑定值；
     * 2. unlockDate 按日期格式输出，便于编辑页直接回填；
     * 3. 仅返回新字段，不兼容旧版 `isFreeTrial/videoIds`。
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'chapterId' => $this->chapter_id,
            'courseId' => $this->course_id,
            'chapterTitle' => $this->chapter_title,
            'chapterSubtitle' => $this->chapter_subtitle,
            'coverImage' => $this->cover_image,
            'videoId' => $this->videoContent ? (int)$this->videoContent->video_id : null,
            'isFree' => $this->is_free,
            'unlockType' => $this->unlock_type,
            'unlockDays' => $this->unlock_days,
            'unlockDate' => $this->unlock_date ? $this->unlock_date->format('Y-m-d') : null,
            'chapterStartTime' => $this->chapter_start_time ? $this->chapter_start_time->format('Y-m-d H:i:s') : null,
            'chapterEndTime' => $this->chapter_end_time ? $this->chapter_end_time->format('Y-m-d H:i:s') : null,
            'status' => $this->status,
            'sortOrder' => $this->sort_order,
            'createTime' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updateTime' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
