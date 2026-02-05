<?php

namespace App\Http\Resources\App;

use App\Services\AppFileUploadService;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 搜索全部 - 课程资源类
 */
class SearchAllCourseResource extends JsonResource
{
    /**
     * 是否正在学习（外部注入）
     *
     * @var bool
     */
    public $isLearning = false;

    /**
     * 设置是否正在学习
     *
     * @param bool $isLearning
     * @return $this
     */
    public function setIsLearning(bool $isLearning)
    {
        $this->isLearning = $isLearning;
        return $this;
    }

    /**
     * 转换资源为数组
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        $coverImage = $this->cover_image;
        if ($coverImage && stripos($coverImage, 'http') !== 0) {
            $coverImage = (new AppFileUploadService())->generateFileUrl($coverImage);
        }

        return [
            'id' => 'course-' . $this->course_id,
            'title' => $this->course_title ?? '',
            'subtitle' => $this->course_subtitle ?? '',
            'price' => (float) ($this->current_price ?? 0),
            'originalPrice' => (float) ($this->original_price ?? 0),
            'cover' => $coverImage ?? '',
            'lessonCount' => $this->total_chapter ?? 0,
            'isLearning' => $this->isLearning,
        ];
    }
}
