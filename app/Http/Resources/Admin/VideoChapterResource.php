<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 录播课章节详情资源
 */
class VideoChapterResource extends JsonResource
{
    public function toArray($request)
    {
        $videoContent = $this->videoContent;

        return [
            // 章节基础信息
            'chapterId' => $this->chapter_id,
            'courseId' => $this->course_id,
            'chapterNo' => $this->chapter_no,
            'chapterTitle' => $this->chapter_title,
            'chapterSubtitle' => $this->chapter_subtitle,
            'coverImage' => $this->cover_image,
            'brief' => $this->brief,
            'isFree' => $this->is_free,
            'isPreview' => $this->is_preview,
            'unlockType' => $this->unlock_type,
            'unlockDays' => $this->unlock_days,
            'unlockDate' => $this->unlock_date ? $this->unlock_date->format('Y-m-d') : null,
            'unlockTime' => $this->unlock_time,
            'hasHomework' => $this->has_homework,
            'homeworkRequired' => $this->homework_required,
            'duration' => $this->duration,
            'minLearnTime' => $this->min_learn_time,
            'allowSkip' => $this->allow_skip,
            'allowSpeed' => $this->allow_speed,
            'viewCount' => $this->view_count,
            'completeCount' => $this->complete_count,
            'homeworkCount' => $this->homework_count,
            'sortOrder' => $this->sort_order,
            'status' => $this->status,
            'createdAt' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updatedAt' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
            // 视频内容
            'videoContent' => $videoContent ? [
                'id' => $videoContent->id,
                'videoUrl' => $videoContent->video_url,
                'videoId' => $videoContent->video_id,
                'videoSource' => $videoContent->video_source,
                'duration' => $videoContent->duration,
                'width' => $videoContent->width,
                'height' => $videoContent->height,
                'fileSize' => $videoContent->file_size,
                'coverImage' => $videoContent->cover_image,
                'qualityList' => $videoContent->quality_list,
                'subtitles' => $videoContent->subtitles,
                'attachments' => $videoContent->attachments,
                'allowDownload' => $videoContent->allow_download,
                'drmEnabled' => $videoContent->drm_enabled,
            ] : null,
        ];
    }
}
