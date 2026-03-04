<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 录播课章节详情资源 - 用于详情/编辑页面
 */
class VideoChapterResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'chapterId' => $this->chapter_id,
            'courseId' => $this->course_id,
            'chapterTitle' => $this->chapter_title,
            'unlockTime' => $this->unlock_time ? $this->unlock_time->format('Y-m-d H:i:s') : null,
            'isFreeTrial' => $this->is_free_trial,
            'status' => $this->status,
            'sortOrder' => $this->sort_order,
            'createTime' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updateTime' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,

            // 关联视频ID列表
            'videoIds' => $this->whenLoaded('contents', function () {
                return $this->contents->pluck('video_id')->toArray();
            }),
        ];
    }
}
