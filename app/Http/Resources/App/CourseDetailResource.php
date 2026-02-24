<?php

namespace App\Http\Resources\App;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * App端课程详情资源
 */
class CourseDetailResource extends JsonResource
{
    public function toArray($request)
    {
        $isFree = $this->is_free == 1;

        return [
            'contentImage' => !empty($this->banner_images) ? $this->banner_images[0] : null,
            'limitPrice' => $this->current_price,
            'originalPrice' => $this->original_price,
            'discountPoints' => $isFree ? (string) (intval(floatval($this->original_price) * 100)) : null,
            'buttonText' => $isFree ? '免费领取课程' : '立即购买',
            'buttonActionType' => $isFree ? 'free_receive' : 'buy',
        ];
    }
}
