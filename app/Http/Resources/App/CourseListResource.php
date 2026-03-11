<?php

namespace App\Http\Resources\App;

use App\Http\Resources\App\Concerns\FormatsCoursePrice;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * App 端选课中心课程卡片资源。
 *
 * 字段约定：
 * 1. price / originalPrice 均基于课程价格字段去尾零后输出；
 * 2. 金额字段保持 string，兼容现有前端渲染口径。
 */
class CourseListResource extends JsonResource
{
    use FormatsCoursePrice;

    /**
     * 输出选课中心课程卡片。
     *
     * @param \Illuminate\Http\Request $request
     * @return array{id:int,cover:string,title:string,desc:string,price:string,originalPrice:string}
     */
    public function toArray($request)
    {
        return [
            'id' => $this->course_id,
            'cover' => $this->cover_image,
            'title' => $this->course_title,
            'desc' => $this->course_subtitle,
            'price' => $this->formatPriceString($this->current_price),
            'originalPrice' => $this->formatPriceString($this->original_price),
        ];
    }
}
