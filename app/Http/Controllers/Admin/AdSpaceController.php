<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdSpaceStoreRequest;
use App\Http\Requests\Admin\AdSpaceUpdateRequest;
use App\Http\Requests\Admin\AdSpaceStatusRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\Admin\AdSpaceResource;
use App\Http\Resources\Admin\AdSpaceListResource;
use App\Http\Resources\Admin\AdSpaceSimpleResource;
use App\Services\Admin\AdSpaceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdSpaceController extends Controller
{
    /**
     * @var AdSpaceService
     */
    protected $adSpaceService;

    /**
     * AdSpaceController constructor.
     *
     * @param AdSpaceService $adSpaceService
     */
    public function __construct(AdSpaceService $adSpaceService)
    {
        $this->adSpaceService = $adSpaceService;
    }

    /**
     * 广告位列表
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function list(Request $request)
    {
        $filters = [
            'spaceName' => $request->input('spaceName'),
            'spaceCode' => $request->input('spaceCode'),
            'status' => $request->input('status'),
            'platform' => $request->input('platform'),
        ];

        $pageNum = (int) $request->input('pageNum', 1);
        $pageSize = (int) $request->input('pageSize', 10);

        $paginator = $this->adSpaceService->getList($filters, $pageNum, $pageSize);

        return ApiResponse::paginate($paginator, AdSpaceListResource::class, '查询成功');
    }

    /**
     * 广告位详情
     *
     * @param int $spaceId
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($spaceId)
    {
        $space = $this->adSpaceService->getDetail((int) $spaceId);

        if (!$space) {
            return ApiResponse::error('广告位不存在');
        }

        return ApiResponse::resource($space, AdSpaceResource::class, '查询成功');
    }

    /**
     * 新增广告位
     *
     * @param AdSpaceStoreRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(AdSpaceStoreRequest $request)
    {
        try {
            $data = [
                'spaceName' => $request->input('spaceName'),
                'spaceCode' => $request->input('spaceCode'),
                'platform' => $request->input('platform', 0),
                'width' => $request->input('width', 0),
                'height' => $request->input('height', 0),
                // 默认值与数据库 max_ads > 0 约束保持一致，避免落库失败。
                'maxAds' => $request->input('maxAds', 1),
                'status' => $request->input('status', 1),
            ];

            $this->adSpaceService->create($data);

            return ApiResponse::success([], '新增成功');
        } catch (\Exception $e) {
            Log::error('新增广告位失败', [
                'action' => 'store',
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 更新广告位
     *
     * @param AdSpaceUpdateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(AdSpaceUpdateRequest $request)
    {
        try {
            $spaceId = (int) $request->input('spaceId');

            $data = [
                'spaceName' => $request->input('spaceName'),
                'spaceCode' => $request->input('spaceCode'),
                'platform' => $request->input('platform'),
                'width' => $request->input('width'),
                'height' => $request->input('height'),
                'maxAds' => $request->input('maxAds'),
                'status' => $request->input('status'),
            ];

            $result = $this->adSpaceService->update($spaceId, $data);

            if (!$result) {
                return ApiResponse::error('广告位不存在');
            }

            return ApiResponse::success([], '修改成功');
        } catch (\Exception $e) {
            Log::error('更新广告位失败', [
                'action' => 'update',
                'space_id' => $request->input('spaceId'),
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 删除广告位（仅支持单个）
     *
     * 规则：
     * 1. 路由参数仅接收单个 space_id；
     * 2. 若广告位下存在广告内容，直接拒绝删除；
     * 3. 返回成功仅表示软删除成功，不代表物理删除。
     *
     * @param int $spaceId 广告位ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $spaceId)
    {
        try {
            if ($this->adSpaceService->hasAdItems($spaceId)) {
                return ApiResponse::error('广告位下存在广告内容，无法删除');
            }

            $deleted = $this->adSpaceService->delete($spaceId);

            if ($deleted) {
                return ApiResponse::success([], '删除成功');
            } else {
                return ApiResponse::error('删除失败，广告位不存在');
            }
        } catch (\Exception $e) {
            Log::error('删除广告位失败', [
                'action' => 'destroy',
                'space_id' => $spaceId,
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 修改广告位状态
     *
     * @param AdSpaceStatusRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changeStatus(AdSpaceStatusRequest $request)
    {
        try {
            $spaceId = (int) $request->input('spaceId');
            $status = (int) $request->input('status');

            $result = $this->adSpaceService->changeStatus($spaceId, $status);

            if (!$result) {
                return ApiResponse::error('广告位不存在');
            }

            return ApiResponse::success([], '修改成功');
        } catch (\Exception $e) {
            Log::error('修改广告位状态失败', [
                'action' => 'changeStatus',
                'space_id' => $request->input('spaceId'),
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 下拉选项列表
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function optionselect()
    {
        $options = $this->adSpaceService->getOptions();

        return ApiResponse::collection($options, AdSpaceSimpleResource::class, '查询成功');
    }
}
