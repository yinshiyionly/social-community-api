<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\App\AdListRequest;
use App\Http\Resources\App\AdItemResource;
use App\Http\Resources\App\AppApiResponse;
use App\Models\App\AppAdSpace;
use App\Services\App\AdService;
use Illuminate\Support\Facades\Log;

class AdController extends Controller
{
    /**
     * @var AdService
     */
    protected $adService;

    public function __construct(AdService $adService)
    {
        $this->adService = $adService;
    }

    /**
     * 获取广告列表
     */
    public function list(AdListRequest $request)
    {
        $spaceCode = $request->input('spaceCode');
        $platform = $request->input('platform', AppAdSpace::PLATFORM_ALL);

        try {
            $adItems = $this->adService->getAdList($spaceCode, $platform);

            return AppApiResponse::collection($adItems, AdItemResource::class);
        } catch (\Exception $e) {
            Log::error('获取广告列表失败', [
                'space_code' => $spaceCode,
                'platform' => $platform,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }
}
