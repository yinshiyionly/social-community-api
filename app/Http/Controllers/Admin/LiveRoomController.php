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
                'liveType'     => $request->input('liveType'),
                'liveStatus'   => $request->input('liveStatus'),
                'status'       => $request->input('status'),
                'roomTitle'    => $request->input('roomTitle'),
                'anchorName'   => $request->input('anchorName'),
                'livePlatform' => $request->input('livePlatform'),
                'beginTime'    => $request->input('beginTime'),
                'endTime'      => $request->input('endTime'),
            ];

            $pageNum = (int) $request->input('pageNum', 1);
            $pageSize = (int) $request->input('pageSize', 10);

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
            $room = $this->liveRoomService->getDetail((int) $roomId);

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
                'roomTitle'          => $request->input('roomTitle'),
                'liveType'           => $request->input('liveType'),
                'roomCover'          => $request->input('roomCover'),
                'roomIntro'          => $request->input('roomIntro'),
                'videoUrl'           => $request->input('videoUrl'),
                'livePlatform'       => $request->input('livePlatform'),
                'pushUrl'            => $request->input('pushUrl'),
                'pullUrl'            => $request->input('pullUrl'),
                'anchorName'         => $request->input('anchorName'),
                'anchorAvatar'       => $request->input('anchorAvatar'),
                'scheduledStartTime' => $request->input('scheduledStartTime'),
                'scheduledEndTime'   => $request->input('scheduledEndTime'),
                'liveDuration'       => $request->input('liveDuration'),
                'allowChat'          => $request->input('allowChat'),
                'allowGift'          => $request->input('allowGift'),
                'allowLike'          => $request->input('allowLike'),
                'password'           => $request->input('password'),
                'extConfig'          => $request->input('extConfig'),
                'status'             => $request->input('status'),
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
            $roomId = (int) $request->input('roomId');

            $data = [
                'roomTitle'          => $request->input('roomTitle'),
                'roomCover'          => $request->input('roomCover'),
                'roomIntro'          => $request->input('roomIntro'),
                'videoUrl'           => $request->input('videoUrl'),
                'pushUrl'            => $request->input('pushUrl'),
                'pullUrl'            => $request->input('pullUrl'),
                'anchorName'         => $request->input('anchorName'),
                'anchorAvatar'       => $request->input('anchorAvatar'),
                'scheduledStartTime' => $request->input('scheduledStartTime'),
                'scheduledEndTime'   => $request->input('scheduledEndTime'),
                'liveDuration'       => $request->input('liveDuration'),
                'allowChat'          => $request->input('allowChat'),
                'allowGift'          => $request->input('allowGift'),
                'allowLike'          => $request->input('allowLike'),
                'password'           => $request->input('password'),
                'extConfig'          => $request->input('extConfig'),
                'status'             => $request->input('status'),
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
     * 删除直播间（支持批量）
     *
     * @param string $roomIds 逗号分隔的直播间ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($roomIds)
    {
        try {
            $ids = array_map('intval', explode(',', $roomIds));

            $result = $this->liveRoomService->delete($ids);

            if (!$result['success']) {
                return ApiResponse::error($result['error']);
            }

            return ApiResponse::success([], '删除成功');
        } catch (\Exception $e) {
            Log::error('直播间操作失败', [
                'action' => 'destroy',
                'room_ids' => $roomIds,
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
            $roomId = (int) $request->input('roomId');
            $status = (int) $request->input('status');

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
