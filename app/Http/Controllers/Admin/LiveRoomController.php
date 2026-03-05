<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LiveRoomStoreRequest;
use App\Http\Requests\Admin\LiveRoomUpdateRequest;
use App\Http\Requests\Admin\LiveRoomStatusRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\Admin\LiveRoomResource;
use App\Http\Resources\Admin\LiveRoomListResource;
use App\Services\Admin\LiveRoomService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LiveRoomController extends Controller
{
    /**
     * @var LiveRoomService
     */
    protected $liveRoomService;

    /**
     * LiveRoomController constructor.
     *
     * @param LiveRoomService $liveRoomService
     */
    public function __construct(LiveRoomService $liveRoomService)
    {
        $this->liveRoomService = $liveRoomService;
    }

    /**
     * 直播间列表
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function list(Request $request)
    {
        try {
            $filters = [
                'liveType' => $request->input('liveType'),
                'liveStatus' => $request->input('liveStatus'),
                'status' => $request->input('status'),
                'roomTitle' => $request->input('roomTitle'),
                'anchorName' => $request->input('anchorName'),
                'livePlatform' => $request->input('livePlatform'),
                'beginTime' => $request->input('beginTime'),
                'endTime' => $request->input('endTime'),
            ];

            $pageNum = (int)$request->input('pageNum', 1);
            $pageSize = (int)$request->input('pageSize', 10);

            $paginator = $this->liveRoomService->getList($filters, $pageNum, $pageSize);

            return ApiResponse::paginate($paginator, LiveRoomListResource::class, '查询成功');
        } catch (\Exception $e) {
            Log::error('直播间操作失败', [
                'action' => 'list',
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 直播间详情
     *
     * @param int $roomId
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($roomId)
    {
        try {
            $room = $this->liveRoomService->getDetail((int)$roomId);

            if (!$room) {
                return ApiResponse::error('直播间不存在');
            }

            return ApiResponse::resource($room, LiveRoomResource::class, '查询成功');
        } catch (\Exception $e) {
            Log::error('直播间操作失败', [
                'action' => 'show',
                'room_id' => $roomId,
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 创建直播间
     *
     * @param LiveRoomStoreRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(LiveRoomStoreRequest $request)
    {
        try {
            $data = [
                'roomTitle' => $request->input('roomTitle'),
                'liveType' => $request->input('liveType'),
                'scheduledStartTime' => $request->input('scheduledStartTime'),
                'scheduledEndTime' => $request->input('scheduledEndTime'),
            ];

            $this->liveRoomService->create($data);

            return ApiResponse::success([], '新增成功');
        } catch (\Exception $e) {
            Log::error('直播间操作失败', [
                'action' => 'store',
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 更新直播间
     *
     * @param LiveRoomUpdateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(LiveRoomUpdateRequest $request)
    {
        try {
            $roomId = (int)$request->input('roomId');

            $data = [
                'roomTitle' => $request->input('roomTitle'),
                'liveType' => $request->input('liveType'),
                'scheduledStartTime' => $request->input('scheduledStartTime'),
                'scheduledEndTime' => $request->input('scheduledEndTime'),
            ];

            $result = $this->liveRoomService->update($roomId, $data);

            if (!$result['success']) {
                return ApiResponse::error($result['error']);
            }

            return ApiResponse::success([], '修改成功');
        } catch (\Exception $e) {
            Log::error('直播间操作失败', [
                'action' => 'update',
                'room_id' => $request->input('roomId'),
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 删除直播间-不支持批量删除
     *
     * @param int $roomId 直播间ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($roomId)
    {
        try {
            $roomId = (int) $roomId;

            // 直播间是否被直播课程章节关联使用
            if ($this->liveRoomService->isUsedByLiveCourseChapter($roomId)) {
                return ApiResponse::error('直播间已被直播课程章节使用，无法删除');
            }

            // 执行百家云删除房间逻辑
            $result = $this->liveRoomService->delete($roomId);

            if (!$result['success']) {
                return ApiResponse::error($result['error']);
            }

            return ApiResponse::success([], '删除成功');
        } catch (\Exception $e) {
            Log::error('直播间操作失败', [
                'action' => 'destroy',
                'room_id' => $roomId,
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 修改直播间状态
     *
     * @param LiveRoomStatusRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changeStatus(LiveRoomStatusRequest $request)
    {
        try {
            $roomId = (int)$request->input('roomId');
            $status = (int)$request->input('status');

            $result = $this->liveRoomService->changeStatus($roomId, $status);

            if (!$result) {
                return ApiResponse::error('直播间不存在');
            }

            return ApiResponse::success([], '修改成功');
        } catch (\Exception $e) {
            Log::error('直播间操作失败', [
                'action' => 'changeStatus',
                'room_id' => $request->input('roomId'),
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }
}
