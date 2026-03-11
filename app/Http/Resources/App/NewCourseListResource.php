<?php

namespace App\Http\Resources\App;

use App\Http\Resources\App\Concerns\FormatsCoursePrice;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * App 端好课上新/名师好课列表资源。
 *
 * 字段约定：
 * 1. price / originalPrice 统一按去尾零规则输出；
 * 2. 金额字段保持 string，确保与课程模块已有消费方兼容。
 */
class NewCourseListResource extends JsonResource
{
    use FormatsCoursePrice;

    /**
     * 输出课程列表项。
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
            'desc' => $this->teacher ? $this->teacher->brief : '',
            'price' => $this->formatPriceString($this->current_price),
            'originalPrice' => $this->formatPriceString($this->original_price),
        ];
    }
}
