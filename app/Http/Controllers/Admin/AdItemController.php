<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdItemStoreRequest;
use App\Http\Requests\Admin\AdItemUpdateRequest;
use App\Http\Requests\Admin\AdItemStatusRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\Admin\AdItemResource;
use App\Http\Resources\Admin\AdItemListResource;
use App\Services\Admin\AdItemService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdItemController extends Controller
{
    /**
     * @var AdItemService
     */
    protected $adItemService;

    /**
     * AdItemController constructor.
     *
     * @param AdItemService $adItemService
     */
    public function __construct(AdItemService $adItemService)
    {
        $this->adItemService = $adItemService;
    }

    /**
     * 广告内容列表
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function list(Request $request)
    {
        $filters = [
            'spaceId' => $request->input('spaceId'),
            'adTitle' => $request->input('adTitle'),
            'adType' => $request->input('adType'),
            'status' => $request->input('status'),
            'beginTime' => $request->input('beginTime'),
            'endTime' => $request->input('endTime'),
        ];

        $pageNum = (int) $request->input('pageNum', 1);
        $pageSize = (int) $request->input('pageSize', 10);

        $paginator = $this->adItemService->getList($filters, $pageNum, $pageSize);

        return ApiResponse::paginate($paginator, AdItemListResource::class, '查询成功');
    }

    /**
     * 广告内容详情
     *
     * @param int $adId
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($adId)
    {
        $adItem = $this->adItemService->getDetail((int) $adId);

        if (!$adItem) {
            return ApiResponse::error('广告内容不存在');
        }

        return ApiResponse::resource($adItem, AdItemResource::class, '查询成功');
    }

    /**
     * 新增广告内容
     *
     * @param AdItemStoreRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(AdItemStoreRequest $request)
    {
        try {
            $data = [
                'spaceId' => $request->input('spaceId'),
                'adTitle' => $request->input('adTitle'),
                'adType' => $request->input('adType'),
                'contentUrl' => $request->input('contentUrl', ''),
                'targetType' => $request->input('targetType', 'none'),
                'targetUrl' => $request->input('targetUrl', ''),
                'sortNum' => $request->input('sortNum', 0),
                'status' => $request->input('status', 1),
                'startTime' => $request->input('startTime'),
                'endTime' => $request->input('endTime'),
                'extJson' => $request->input('extJson', []),
            ];

            $this->adItemService->create($data);

            return ApiResponse::success([], '新增成功');
        } catch (\Exception $e) {
            Log::error('新增广告内容失败', [
                'action' => 'store',
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 更新广告内容
     *
     * @param AdItemUpdateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(AdItemUpdateRequest $request)
    {
        try {
            $adId = (int) $request->input('adId');

            $data = [
                'spaceId' => $request->input('spaceId'),
                'adTitle' => $request->input('adTitle'),
                'adType' => $request->input('adType'),
                'contentUrl' => $request->input('contentUrl'),
                'targetType' => $request->input('targetType'),
                'targetUrl' => $request->input('targetUrl'),
                'sortNum' => $request->input('sortNum'),
                'status' => $request->input('status'),
                'startTime' => $request->input('startTime'),
                'endTime' => $request->input('endTime'),
                'extJson' => $request->input('extJson'),
            ];

            $result = $this->adItemService->update($adId, $data);

            if (!$result) {
                return ApiResponse::error('广告内容不存在');
            }

            return ApiResponse::success([], '修改成功');
        } catch (\Exception $e) {
            Log::error('更新广告内容失败', [
                'action' => 'update',
                'ad_id' => $request->input('adId'),
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 删除广告内容（支持批量）
     *
     * @param string $adIds 逗号分隔的广告ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($adIds)
    {
        try {
            $ids = array_map('intval', explode(',', $adIds));

            $deletedCount = $this->adItemService->delete($ids);

            if ($deletedCount > 0) {
                return ApiResponse::success([], '删除成功');
            } else {
                return ApiResponse::error('删除失败，广告内容不存在');
            }
        } catch (\Exception $e) {
            Log::error('删除广告内容失败', [
                'action' => 'destroy',
                'ad_ids' => $adIds,
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 修改广告内容状态
     *
     * @param AdItemStatusRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changeStatus(AdItemStatusRequest $request)
    {
        try {
            $adId = (int) $request->input('adId');
            $status = (int) $request->input('status');

            $result = $this->adItemService->changeStatus($adId, $status);

            if (!$result) {
                return ApiResponse::error('广告内容不存在');
            }

            return ApiResponse::success([], '修改成功');
        } catch (\Exception $e) {
            Log::error('修改广告内容状态失败', [
                'action' => 'changeStatus',
                'ad_id' => $request->input('adId'),
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }
}
