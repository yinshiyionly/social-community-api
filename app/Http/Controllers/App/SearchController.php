<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\App\SearchAllRequest;
use App\Http\Requests\App\SearchRequest;
use App\Http\Resources\App\AppApiResponse;
use App\Services\App\SearchService;
use Illuminate\Http\Request;
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

    /**
     * 搜索全部（用户+课程混合）
     *
     * @param SearchAllRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchAll(SearchAllRequest $request)
    {
        $keyword = $request->getKeyword();
        $page = $request->getPage();
        $pageSize = $request->getPageSize();

        // 获取当前登录用户ID（可选）
        $memberId = $request->attributes->get('member_id');

        try {
            $result = $this->searchService->searchAll($keyword, $page, $pageSize, $memberId);
            return AppApiResponse::success(['data' => $result]);
        } catch (\Exception $e) {
            Log::error('搜索全部失败', [
                'keyword' => $keyword,
                'page' => $page,
                'pageSize' => $pageSize,
                'error' => $e->getMessage(),
            ]);
            return AppApiResponse::serverError();
        }
    }

    /**
     * 搜索用户
     *
     * @param SearchAllRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchUser(SearchAllRequest $request)
    {
        $keyword = $request->getKeyword();
        $page = $request->getPage();
        $pageSize = $request->getPageSize();

        // 获取当前登录用户ID（可选）
        $memberId = $request->attributes->get('member_id');

        try {
            $result = $this->searchService->searchUser($keyword, $page, $pageSize, $memberId);
            return AppApiResponse::success(['data' => $result]);
        } catch (\Exception $e) {
            Log::error('搜索用户失败', [
                'keyword' => $keyword,
                'page' => $page,
                'pageSize' => $pageSize,
                'error' => $e->getMessage(),
            ]);
            return AppApiResponse::serverError();
        }
    }

    /**
     * 搜索课程
     *
     * @param SearchAllRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchCourse(SearchAllRequest $request)
    {
        $keyword = $request->getKeyword();
        $page = $request->getPage();
        $pageSize = $request->getPageSize();

        // 获取当前登录用户ID（可选）
        $memberId = $request->attributes->get('member_id');

        try {
            $result = $this->searchService->searchCourse($keyword, $page, $pageSize, $memberId);
            return AppApiResponse::success(['data' => $result]);
        } catch (\Exception $e) {
            Log::error('搜索课程失败', [
                'keyword' => $keyword,
                'page' => $page,
                'pageSize' => $pageSize,
                'error' => $e->getMessage(),
            ]);
            return AppApiResponse::serverError();
        }
    }
}
