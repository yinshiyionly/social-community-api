<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 课表章节资源 - 章节基础信息 + 对应类型的内容
 */
class CourseScheduleChapterResource extends JsonResource
{
    public function toArray($request)
    {
        $data = [
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
            'unlockDate' => $this->unlock_date,
            'unlockTime' => $this->unlock_time,
            'chapterStartTime' => $this->chapter_start_time ? $this->chapter_start_time->format('Y-m-d H:i:s') : null,
            'chapterEndTime' => $this->chapter_end_time ? $this->chapter_end_time->format('Y-m-d H:i:s') : null,
            'duration' => $this->duration,
            'hasHomework' => $this->has_homework,
            'sortOrder' => $this->sort_order,
            'status' => $this->status,
        ];

        // 根据课程类型附加对应的内容信息
        $data['videoContent'] = $this->whenLoaded('videoContent', function () {
            return $this->formatVideoContent($this->videoContent);
        });

        $data['liveContent'] = $this->whenLoaded('liveContent', function () {
            return $this->formatLiveContent($this->liveContent);
        });

        $data['articleContent'] = $this->whenLoaded('articleContent', function () {
            return $this->formatArticleContent($this->articleContent);
        });

        $data['audioContent'] = $this->whenLoaded('audioContent', function () {
            return $this->formatAudioContent($this->audioContent);
        });

        return $data;
    }


    /**
     * 格式化视频内容
     */
    private function formatVideoContent($content)
    {
        if (!$content) {
            return null;
        }
        return [
            'videoUrl' => $content->video_url,
            'videoId' => $content->video_id,
            'videoSource' => $content->video_source,
            'duration' => $content->duration,
            'width' => $content->width,
            'height' => $content->height,
            'fileSize' => $content->file_size,
            'coverImage' => $content->cover_image,
        ];
    }

    /**
     * 格式化直播内容
     */
    private function formatLiveContent($content)
    {
        if (!$content) {
            return null;
        }
        return [
            'livePlatform' => $content->live_platform,
            'liveRoomId' => $content->live_room_id,
            'liveCover' => $content->live_cover,
            'liveStartTime' => $content->live_start_time,
            'liveEndTime' => $content->live_end_time,
            'liveDuration' => $content->live_duration,
            'liveStatus' => $content->live_status,
            'hasPlayback' => $content->has_playback,
            'playbackUrl' => $content->playback_url,
            'allowChat' => $content->allow_chat,
            'onlineCount' => $content->online_count,
            'maxOnlineCount' => $content->max_online_count,
        ];
    }

    /**
     * 格式化图文内容
     */
    private function formatArticleContent($content)
    {
        if (!$content) {
            return null;
        }
        return [
            'contentHtml' => $content->content_html,
            'images' => $content->images,
            'attachments' => $content->attachments,
            'wordCount' => $content->word_count,
            'readTime' => $content->read_time,
        ];
    }

    /**
     * 格式化音频内容
     */
    private function formatAudioContent($content)
    {
        if (!$content) {
            return null;
        }
        return [
            'audioUrl' => $content->audio_url,
            'audioId' => $content->audio_id,
            'audioSource' => $content->audio_source,
            'duration' => $content->duration,
            'fileSize' => $content->file_size,
            'coverImage' => $content->cover_image,
            'transcript' => $content->transcript,
            'allowDownload' => $content->allow_download,
            'backgroundPlay' => $content->background_play,
        ];
    }
}
