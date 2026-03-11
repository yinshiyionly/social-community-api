<?php

namespace App\Http\Resources\App;

use App\Http\Resources\App\Concerns\FormatsCoursePrice;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * App 端课程详情资源。
 *
 * 字段约定：
 * 1. limitPrice / originalPrice 使用课程金额去尾零展示；
 * 2. discountPoints 沿用原价换算积分逻辑，不受展示格式化影响。
 */
class CourseDetailResource extends JsonResource
{
    use FormatsCoursePrice;

    /**
     * 输出课程详情价格与操作信息。
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $isFree = $this->is_free == 1;

        return [
            'contentImage' => $this->item_image ?: (!empty($this->banner_images) ? $this->banner_images[0] : null),
            'limitPrice' => $this->formatPriceString($this->current_price),
            'originalPrice' => $this->formatPriceString($this->original_price),
            'discountPoints' => $isFree ? (string) (intval(floatval($this->original_price) * 100)) : null,
            'buttonText' => $isFree ? '免费领取课程' : '立即购买',
            'buttonActionType' => $isFree ? 'free_receive' : 'buy',
        ];
    }
}
