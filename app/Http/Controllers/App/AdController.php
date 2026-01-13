<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponse;
use App\Models\App\AppAdItem;
use App\Models\App\AppAdSpace;
use Illuminate\Http\Request;

class AdController extends Controller
{
    /**
     * 获取广告列表
     */
    public function list(Request $request)
    {
        $spaceCode = $request->input('spaceCode');

        if (empty($spaceCode)) {
            return ApiResponse::error('广告位编码不能为空');
        }

        // 查找广告位
        $adSpace = AppAdSpace::byCode($spaceCode)->enabled()->first();

        if (!$adSpace) {
            return ApiResponse::success([]);
        }

        // 获取有效的广告内容
        $adItems = AppAdItem::bySpace($adSpace->space_id)
            ->online()
            ->inEffect()
            ->orderByPriority()
            ->limit($adSpace->max_ads)
            ->get();

        $data = $adItems->map(function ($item) {
            return [
                'adId' => $item->ad_id,
                'imageUrl' => $item->content_url,
                'linkUrl' => $item->target_url,
                'spaceId' => $item->space_id,
                'targetType' => $item->target_type,
            ];
        });
        dd(
            $data
        );

        return ApiResponse::success($data);
    }
}
