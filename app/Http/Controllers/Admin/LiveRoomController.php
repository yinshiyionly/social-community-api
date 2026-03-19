<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LiveRoomRedPacketSendRequest;
use App\Http\Requests\Admin\LiveRoomStoreRequest;
use App\Http\Requests\Admin\LiveRoomStatusRequest;
use App\Http\Requests\Admin\LiveRoomUpdateRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\Admin\LiveRoomMockPlaybackListResource;
use App\Http\Resources\Admin\LiveRoomMockVideoListResource;
use App\Http\Resources\Admin\LiveRoomResource;
use App\Http\Resources\Admin\LiveRoomListResource;
use App\Models\App\AppLiveRoom;
use App\Services\Admin\LiveRoomService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Admin 直播间管理控制器。
 *
 * 职责：
 * 1. 提供直播间增删改查与状态管理接口；
 * 2. 统一处理请求参数到 Service 层字段映射；
 * 3. 兜底记录异常日志并返回通用错误响应，避免泄露内部异常细节。
 */
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
     * 直播间常量配置项
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function constants()
    {
        $data = [
            // 直播类型
            'liveTypeOptions'        => [
                ['label' => '主播模式', 'value' => AppLiveRoom::LIVE_TYPE_REAL],
                ['label' => '伪直播', 'value' => AppLiveRoom::LIVE_TYPE_PSEUDO],
                // ['label' => '24小时直播', 'value' => AppCourseBase::PAY_TYPE_HIGHER],
            ],
            // 直播状态
            'liveStatusOptions'      => [
                ['label' => '未开始', 'value' => AppLiveRoom::LIVE_STATUS_NOT_STARTED],
                ['label' => '直播中', 'value' => AppLiveRoom::LIVE_STATUS_LIVING],
                ['label' => '已结束', 'value' => AppLiveRoom::LIVE_STATUS_ENDED],
                ['label' => '已取消', 'value' => AppLiveRoom::LIVE_STATUS_CANCELLED]
            ],
            // 伪直播素材来源
            'mockVideoSourceOptions' => [
                ['label' => '百家云回放', 'value' => AppLiveRoom::MOCK_VIDEO_SOURCE_PLAYBACK],
                ['label' => '百家云点播视频', 'value' => AppLiveRoom::MOCK_VIDEO_SOURCE_VIDEO],
                ['label' => '系统视频文件', 'value' => AppLiveRoom::MOCK_VIDEO_SOURCE_SYSTEM]
            ],
            // APP 端模版样式
            'appTemplateOptions'     => [
                ['label' => '横屏', 'value' => AppLiveRoom::APP_TEMPLATE_HORIZONTAL],
                ['label' => '竖屏', 'value' => AppLiveRoom::APP_TEMPLATE_VERTICAL]
            ],
            // 带货模版
            'enableLiveSellOptions'  => [
                ['label' => '禁用带货模版', 'value' => AppLiveRoom::ENABLE_LIVE_SELL_OFF],
                ['label' => '视频带货模版', 'value' => AppLiveRoom::ENABLE_LIVE_SELL_VIDEO],
                ['label' => 'PPT带货模版', 'value' => AppLiveRoom::ENABLE_LIVE_SELL_PPT]
            ],
            // 是否展示在首页
            'isShowIndexOptions'     => [
                ['label' => '是-展示在首页', 'value' => AppLiveRoom::IS_SHOW_INDEX_YES],
                ['label' => '否-不展示在首页', 'value' => AppLiveRoom::IS_SHOW_INDEX_NO],
            ],
        ];

        return ApiResponse::success(['data' => $data]);
    }

    /**
     * 获取伪直播可选的点播视频分页列表。
     *
     * 接口用途：
     * - 仅用于 Admin 创建直播时选择百家云点播视频；
     * - 前端选择后透传 videoId 到创建接口的 mockVideoId 字段。
     *
     * 关键输入：
     * - pageNum/pageSize：分页参数，默认 1/10；
     * - videoId：可选精确匹配；
     * - name：可选视频名称模糊匹配。
     *
     * 关键输出：
     * - 返回 ApiResponse::paginate 结构（code/msg/total/rows）；
     * - rows 仅返回创建直播所需的轻量视频字段。
     *
     * 失败分支：
     * - 异常统一记录日志并返回通用错误，避免泄露内部异常细节。
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function mockVideoList(Request $request)
    {
        try {
            $filters = [
                'videoId' => $request->input('videoId'),
                'name'    => $request->input('name'),
            ];

            $pageNum = (int)$request->input('pageNum', 1);
            $pageSize = (int)$request->input('pageSize', 10);

            $paginator = $this->liveRoomService->getMockVideoList($filters, $pageNum, $pageSize);

            return ApiResponse::paginate($paginator, LiveRoomMockVideoListResource::class, '查询成功');
        } catch (\Exception $e) {
            Log::error('直播间操作失败', [
                'action' => 'mockVideoList',
                'error'  => $e->getMessage(),
            ]);

            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 获取伪直播可选的回放分页列表。
     *
     * 接口用途：
     * - 仅用于 Admin 创建伪直播时选择百家云回放；
     * - 前端选择后透传 mockRoomId 到创建接口的 mockRoomId 字段。
     *
     * 关键输入：
     * - pageNum/pageSize：分页参数，默认 1/10；
     * - mockRoomId：可选精确匹配 third_party_room_id；
     * - name：可选回放名称模糊匹配。
     *
     * 关键输出：
     * - 返回 ApiResponse::paginate 结构（code/msg/total/rows）；
     * - rows 仅返回创建伪直播所需的轻量字段。
     *
     * 失败分支：
     * - 异常统一记录日志并返回通用错误，避免泄露内部异常细节。
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function mockPlaybackList(Request $request)
    {
        try {
            $filters = [
                'mockRoomId' => $request->input('mockRoomId'),
                'name'       => $request->input('name'),
            ];

            $pageNum = (int)$request->input('pageNum', 1);
            $pageSize = (int)$request->input('pageSize', 10);

            $paginator = $this->liveRoomService->getMockPlaybackList($filters, $pageNum, $pageSize);

            return ApiResponse::paginate($paginator, LiveRoomMockPlaybackListResource::class, '查询成功');
        } catch (\Exception $e) {
            Log::error('直播间操作失败', [
                'action' => 'mockPlaybackList',
                'error'  => $e->getMessage(),
            ]);

            return ApiResponse::error('操作失败，请稍后重试');
        }
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

            $pageNum = (int)$request->input('pageNum', 1);
            $pageSize = (int)$request->input('pageSize', 10);

            $paginator = $this->liveRoomService->getList($filters, $pageNum, $pageSize);

            return ApiResponse::paginate($paginator, LiveRoomListResource::class, '查询成功');
        } catch (\Exception $e) {
            Log::error('直播间操作失败', [
                'action' => 'list',
                'error'  => $e->getMessage(),
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
                'action'  => 'show',
                'room_id' => $roomId,
                'error'   => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 创建直播间
     *
     * 关键约束：
     * 1. roomCover 必须显式传入，确保直播创建后可直接在 App 列表展示；
     * 2. liveType=2 时按 mockVideoSource 二选一校验伪直播素材参数；
     * 3. isShowIndex 创建时必传，保证首页展示策略可追溯；
     * 4. 创建时会调用第三方服务创建房间，本地仅落库必要字段。
     *
     * @param LiveRoomStoreRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(LiveRoomStoreRequest $request)
    {
        try {
            $result = $this->liveRoomService->createByType($request->validated());

            if ($result['success'] === false) {
                return ApiResponse::error($result['error']);
            }

            return ApiResponse::success(['data' => $result['data']], '新增成功');
        } catch (\Exception $e) {
            Log::error('直播间操作失败', [
                'action' => 'store',
                'error'  => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 更新直播间
     *
     * 字段透传规则：
     * - 仅透传请求中“显式出现”的字段，避免未传字段被写成 null；
     * - 显式传 roomCover 为 null 时允许清空封面。
     * - isShowIndex 未传时保持原值，避免误改首页展示状态。
     *
     * @param LiveRoomUpdateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(LiveRoomUpdateRequest $request)
    {
        try {
            $roomId = (int)$request->input('roomId');

            $allowedFields = ['roomTitle', 'roomCover', 'scheduledStartTime', 'scheduledEndTime', 'isShowIndex'];
            $data = [];

            foreach ($allowedFields as $field) {
                // 使用 exists 区分“未传字段”和“显式传 null”，支持清空封面语义。
                if ($request->exists($field)) {
                    $data[$field] = $request->input($field);
                }
            }

            $result = $this->liveRoomService->update($roomId, $data);

            if (!$result['success']) {
                return ApiResponse::error($result['error']);
            }

            return ApiResponse::success([], '修改成功');
        } catch (\Exception $e) {
            Log::error('直播间操作失败', [
                'action'  => 'update',
                'room_id' => $request->input('roomId'),
                'error'   => $e->getMessage(),
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
            $roomId = (int)$roomId;

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
                'action'  => 'destroy',
                'room_id' => $roomId,
                'error'   => $e->getMessage(),
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
                'action'  => 'changeStatus',
                'room_id' => $request->input('roomId'),
                'error'   => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 发送直播红包
     *
     * @param LiveRoomRedPacketSendRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendRedPacket(LiveRoomRedPacketSendRequest $request)
    {
        try {
            $result = $this->liveRoomService->sendRedPacket(
                $request->validated(),
                $request->user()
            );

            if (!$result['success']) {
                return ApiResponse::error($result['error']);
            }

            return ApiResponse::success([
                'data' => $result['data'],
            ], '发送成功');
        } catch (\Exception $e) {
            Log::error('直播间操作失败', [
                'action'  => 'sendRedPacket',
                'room_id' => $request->input('roomId'),
                'error'   => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 直播教室上下课事件回调
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function classCallback(Request $request)
    {
        // 1. 获取参数
        $params = $request->all();
        // 参数中没有 room_id
        if (!isset($params['room_id'])) {
            return response()->json(['code' => 0]);
        }
        // 参数中没有携带 op
        if (!isset($params['op'])) {
            return response()->json(['code' => 0]);
        }

        $update = [];

        // 操作类型 具体 @see https://dev.baijiayun.com/wiki/detail/79#-h162-165
        // 上课 start
        // 下课 end
        // 禁用直播间 forbidden
        // 老师进出教室 teacher_in
        // 老师退出教室 teacher_out
        // 上课时长更新 stat
        switch ($params['op']) {
            case 'start':
                Log::info('直播教室回调-上课', ['params' => $params]);
                // 1. TODO WebSocket 事件
                // 2. 组建更新数据
                $update = [
                    'live_status' => AppLiveRoom::LIVE_STATUS_LIVING
                ];
                break;
            case 'end':
                Log::error('直播教室回调-下课', ['params' => $params]);
                // 1. TODO WebSocket 事件
                // 2. 组建更新数据
                $update = [
                    'live_status' => AppLiveRoom::LIVE_STATUS_ENDED
                ];
                break;
        }
        // 更新数据表中该直播间的状态
        try {
            if (!empty($update)) {
                AppLiveRoom::query()->where(['third_party_room_id' => $params['room_id']])
                    ->update($update);
            }
        } catch (\Exception $e) {
            Log::error('直播教室回调-更新直播数据表失败: ' . $e->getMessage(), ['params' => $params, 'update' => $update]);
        }
        return response()->json(['code' => 0]);
    }
}
