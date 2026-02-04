<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 录播课章节列表资源
 */
class VideoChapterListResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'chapterId' => $this->chapter_id,
            'courseId' => $this->course_id,
            'chapterNo' => $this->chapter_no,
            'chapterTitle' => $this->chapter_title,
            'chapterSubtitle' => $this->chapter_subtitle,
            'coverImage' => $this->cover_image,
            'isFree' => $this->is_free,
            'isPreview' => $this->is_preview,
            'unlockType' => $this->unlock_type,
            'unlockDays' => $this->unlock_days,
            'duration' => $this->duration,
            'viewCount' => $this->view_count,
            'completeCount' => $this->complete_count,
            'sortOrder' => $this->sort_order,
            'status' => $this->status,
            'createdAt' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            // 视频信息
            'videoUrl' => $this->whenLoaded('videoContent', function () {
                return $this->videoContent ? $this->videoContent->video_url : null;
            }),
            'videoDuration' => $this->whenLoaded('videoContent', function () {
                return $this->videoContent ? $this->videoContent->duration : 0;
            }),
        ];
    }
}
