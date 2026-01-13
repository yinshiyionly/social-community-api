<?php

namespace App\Services\App;

use App\Models\App\AppAdItem;
use App\Models\App\AppAdSpace;
use Illuminate\Support\Collection;

class AdService
{
    /**
     * 获取广告列表
     *
     * @param string $spaceCode 广告位编码
     * @param int $platform 平台类型
     * @return Collection
     */
    public function getAdList(string $spaceCode, int $platform = AppAdSpace::PLATFORM_ALL): Collection
    {
        // 查找广告位
        $adSpace = AppAdSpace::byCode($spaceCode)
            ->enabled()
            ->byPlatform($platform)
            ->first();

        if (!$adSpace) {
            return collect([]);
        }

        // 获取有效的广告内容
        return AppAdItem::query()
            ->bySpace($adSpace->space_id)
            ->online()
            ->inEffect()
            ->orderByPriority()
            ->limit($adSpace->max_ads)
            ->get();
    }
}
