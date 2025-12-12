<?php

declare(strict_types=1);

namespace App\Http\Controllers\Detection;

use App\Helper\Volcengine\InsightIndustryTag;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponse;

/**
 * 监测任务配置
 */
class DetectionConfigController extends Controller
{
    /**
     * 获取行业标签
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function industryTag()
    {
        $data = InsightIndustryTag::getIndustryTagList();

        return ApiResponse::success(['data' => $data]);
    }
}
