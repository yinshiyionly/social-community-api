<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 录播课章节列表资源 - 用于列表展示
 */
class VideoChapterListResource extends JsonResource
{
    /**
     * 输出录播章节列表项。
     *
     * 字段约定：
     * 1. videoId 为单选视频ID，来源于 videoContent；
     * 2. unlockDate 使用 `Y-m-d`，章节时间使用 `Y-m-d H:i:s`；
     * 3. 不再返回历史字段别名（如 isFreeTrial/videoIds）。
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
        ];
    }
}
