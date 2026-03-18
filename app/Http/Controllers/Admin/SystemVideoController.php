<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SystemVideoStoreRequest;
use App\Http\Requests\Admin\SystemVideoUpdateRequest;
use App\Http\Resources\Admin\SystemVideoListResource;
use App\Http\Resources\Admin\SystemVideoResource;
use App\Http\Resources\ApiResponse;
use App\Models\App\AppVideoSystem;
use App\Services\Admin\SystemVideoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SystemVideoController extends Controller
{
    /**
     * @var SystemVideoService
     */
    protected $systemVideoService;

    /**
     * @param SystemVideoService $systemVideoService
     */
    public function __construct(SystemVideoService $systemVideoService)
    {
        $this->systemVideoService = $systemVideoService;
    }

    /**
     * 常量选项
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function constants()
    {
        $data = [
            'statusOptions' => AppVideoSystem::getStatusOptions(),
            'sourceOptions' => [
                [
                    'label' => '系统',
                    'value' => AppVideoSystem::SOURCE_SYSTEM,
                ],
            ],
        ];

        return ApiResponse::success(['data' => $data], '查询成功');
    }

    /**
     * 视频列表
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function list(Request $request)
    {
        $filters = [
            'videoId'   => $request->input('videoId'),
            'name'      => $request->input('name'),
            'status'    => $request->input('status'),
            'beginTime' => $request->input('beginTime'),
            'endTime'   => $request->input('endTime'),
        ];

        $pageNum = (int)$request->input('pageNum', 1);
        $pageSize = (int)$request->input('pageSize', 10);

        $paginator = $this->systemVideoService->getList($filters, $pageNum, $pageSize);

        return ApiResponse::paginate($paginator, SystemVideoListResource::class, '查询成功');
    }

    /**
     * 视频详情
     *
     * @param int $videoId
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($videoId)
    {
        $video = $this->systemVideoService->getDetail((int)$videoId);

        if (!$video) {
            return ApiResponse::error('视频不存在');
        }

        return ApiResponse::resource($video, SystemVideoResource::class, '查询成功');
    }

    /**
     * 新增视频
     *
     * @param SystemVideoStoreRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(SystemVideoStoreRequest $request)
    {
        try {
            $data = $request->validated();
            $video = $this->systemVideoService->create($data);

            return ApiResponse::success([
                'data' => [
                    'videoId' => (int)$video->video_id,
                ],
            ], '新增成功');
        } catch (\Exception $e) {
            Log::error('新增系统视频失败', [
                'action' => 'store',
                'error'  => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 更新视频
     *
     * @param SystemVideoUpdateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(SystemVideoUpdateRequest $request)
    {
        try {
            $data = $request->validated();
            $videoId = (int)$data['videoId'];
            unset($data['videoId']);

            $result = $this->systemVideoService->update($videoId, $data);

            if (!$result) {
                return ApiResponse::error('视频不存在');
            }

            return ApiResponse::success([], '修改成功');
        } catch (\Exception $e) {
            Log::error('更新系统视频失败', [
                'action'   => 'update',
                'video_id' => $request->input('videoId'),
                'error'    => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 删除视频-不支持批量删除
     * 软删除
     *
     * @param int $videoId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($videoId)
    {
        try {
            $videoId = (int)$videoId;

            if (empty($videoId)) {
                return ApiResponse::error('参数错误');
            }

            $deletedCount = $this->systemVideoService->delete($videoId);
            if ($deletedCount <= 0) {
                return ApiResponse::error('删除失败，视频不存在');
            }

            return ApiResponse::success([], '删除成功');
        } catch (\Exception $e) {
            Log::error('删除系统视频失败', [
                'action'   => 'destroy',
                'video_id' => $videoId,
                'error'    => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }
}
