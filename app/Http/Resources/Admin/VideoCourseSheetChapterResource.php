<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 录播课课程表章节资源。
 *
 * 职责：
 * 1. 复用章节 all 接口的基础输出结构，兼容排序页；
 * 2. 补充录播视频元数据，减少课程表页二次查询；
 * 3. 仅做字段映射，不承载业务规则判断。
 */
class VideoCourseSheetChapterResource extends JsonResource
{
    /**
     * 输出录播课课程表章节项。
     *
     * 字段约定：
     * 1. videoId 来源于章节视频内容映射；
     * 2. videoUrl/videoDuration/videoDurationText 来源于章节视频内容，缺失时回退默认值；
     * 3. videoTitle/videoCoverImage/videoWidth/videoHeight 为聚合字段，优先系统视频元数据；
     * 4. unlockDate 使用 `Y-m-d`，章节时间使用 `Y-m-d H:i:s`。
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $videoContent = $this->videoContent;

        return [
            'chapterId'         => $this->chapter_id,
            'courseId'          => $this->course_id,
            'chapterTitle'      => $this->chapter_title,
            'chapterSubtitle'   => $this->chapter_subtitle,
            'coverImage'        => $this->cover_image,
            'videoId'           => $videoContent ? (int)$videoContent->video_id : null,
            'videoUrl'          => $videoContent ? $videoContent->video_url : null,
            'videoDuration'     => $videoContent ? (int)$videoContent->duration : 0,
            'videoDurationText' => $videoContent ? $videoContent->formatted_duration : '00:00',
            'videoTitle'        => $this->video_title,
            'videoCoverImage'   => $this->video_cover_image,
            'videoWidth'        => (int)($this->video_width ?? 0),
            'videoHeight'       => (int)($this->video_height ?? 0),
            'isFree'            => $this->is_free,
            'unlockType'        => $this->unlock_type,
            'unlockDays'        => $this->unlock_days,
            'unlockDate'        => $this->unlock_date ? $this->unlock_date->format('Y-m-d') : null,
            'chapterStartTime'  => $this->chapter_start_time ? $this->chapter_start_time->format('Y-m-d H:i:s') : null,
            'chapterEndTime'    => $this->chapter_end_time ? $this->chapter_end_time->format('Y-m-d H:i:s') : null,
            'status'            => $this->status,
            'sortOrder'         => $this->sort_order,
            'createTime'        => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
