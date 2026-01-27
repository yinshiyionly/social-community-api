<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\App\SearchRequest;
use App\Http\Resources\App\AppApiResponse;
use App\Services\App\SearchService;
use Illuminate\Support\Facades\Log;

/**
 * 搜索控制器
 */
class SearchController extends Controller
{
    /**
     * @var SearchService
     */
    protected $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * 搜索接口
     *
     * @param SearchRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(SearchRequest $request)
    {
        $keyword = $request->getKeyword();
        $source = $request->getSource();

        try {
            $result = $this->searchService->search($keyword, $source);
            return AppApiResponse::success(['data' => $result]);
        } catch (\Exception $e) {
            Log::error('搜索失败', [
                'keyword' => $keyword,
                'source' => $source,
                'error' => $e->getMessage(),
            ]);
            return AppApiResponse::serverError();
        }
    }
}
