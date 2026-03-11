<?php

namespace App\Http\Resources\App;

use App\Http\Resources\App\Concerns\FormatsCoursePrice;
use App\Services\AppFileUploadService;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 搜索课程资源类。
 *
 * 字段约定：
 * 1. price / originalPrice 基于课程价格字段去尾零后输出；
 * 2. 搜索场景金额字段保持 number，兼容既有前端类型判断。
 */
class SearchAllCourseResource extends JsonResource
{
    use FormatsCoursePrice;

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
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $coverImage = $this->cover_image;
        if ($coverImage && stripos($coverImage, 'http') !== 0) {
            $coverImage = (new AppFileUploadService())->generateFileUrl($coverImage);
        }

        return [
            'id' => $this->course_id,
            'title' => $this->course_title ?? '',
            'subtitle' => $this->course_subtitle ?? '',
            'price' => $this->formatPriceNumber($this->current_price),
            'originalPrice' => $this->formatPriceNumber($this->original_price),
            'cover' => $coverImage ?? '',
            'lessonCount' => $this->total_chapter ?? 0,
            'isLearning' => $this->isLearning,
        ];
    }
}
