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
        $isFree = $this->isCourseFree();

        return [
            'contentImage' => $this->item_image ?: (!empty($this->banner_images) ? $this->banner_images[0] : null),
            'limitPrice' => $this->formatPriceString($this->current_price),
            'originalPrice' => $this->formatPriceString($this->original_price),
            'discountPoints' => $isFree ? (string) (intval(floatval($this->original_price) * 100)) : null,
            'buttonText' => $isFree ? '免费领取课程' : '立即购买',
            'buttonActionType' => $isFree ? 'free_receive' : 'buy',
        ];
    }

    /**
     * 判断课程是否按免费课程展示购买行为。
     *
     * 规则：
     * 1. is_free=1 直接视为免费课程；
     * 2. is_free=0 时，若 original_price=0（如 0.00）也按免费课程兜底。
     *
     * @return bool
     */
    protected function isCourseFree(): bool
    {
        if ((int) $this->is_free === 1) {
            return true;
        }

        $originalPrice = $this->original_price;

        if (is_string($originalPrice)) {
            $originalPrice = trim($originalPrice);
        }

        if ($originalPrice === '' || !is_numeric($originalPrice)) {
            return false;
        }

        // 历史数据可能存在 is_free 未同步的情况，原价为 0 时仍应展示为免费领取。
        return (float) $originalPrice == 0.0;
    }
}
