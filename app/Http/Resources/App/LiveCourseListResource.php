<?php

namespace App\Http\Resources\App;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * App端大咖直播列表资源
 */
class LiveCourseListResource extends JsonResource
{
    public function toArray($request)
    {
        $liveContent = $this->getFirstLiveContent();

        return [
            'id' => $this->course_id,
            'cover' => $this->cover_image,
            'liveTime' => $liveContent && $liveContent->live_start_time
                ? $liveContent->live_start_time->format('n月j日 H:i')
                : null,
            'title' => $this->course_title,
            'viewCount' => $this->view_count,
            'replayUrl' => $liveContent && $liveContent->has_playback
                ? ($liveContent->playback_url ?: '')
                : '',
        ];
    }

    /**
     * 获取第一个章节的直播内容
     */
    private function getFirstLiveContent()
    {
        if (!$this->relationLoaded('chapters') || $this->chapters->isEmpty()) {
            return null;
        }

        $firstChapter = $this->chapters->first();

        if (!$firstChapter || !$firstChapter->relationLoaded('liveContent')) {
            return null;
        }

        return $firstChapter->liveContent;
    }
}
