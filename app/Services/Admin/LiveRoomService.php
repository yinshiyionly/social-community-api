<?php

namespace App\Services\Admin;

use App\Events\LiveRedPacketSent;
use App\Models\App\AppCourseBase;
use App\Models\App\AppLiveChatMessage;
use App\Models\App\AppLiveRoom;
use App\Models\App\AppLiveRoomStat;
use App\Models\App\AppVideoBaijiayun;
use App\Models\System\SystemUser;
use App\Services\BaijiayunLiveService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 直播间管理服务。
 *
 * 核心职责：
 * 1. 维护直播间基础数据的查询与写入；
 * 2. 协调第三方直播平台创建房间并回填关键标识；
 * 3. 处理直播间状态变更、副作用日志和红包消息写入。
 */
class LiveRoomService
{
    /**
     * 获取直播间列表（分页）
     *
     * @param array $filters 筛选条件
     * @param int $pageNum 页码
     * @param int $pageSize 每页数量
     * @return LengthAwarePaginator
     */
    public function getList(array $filters, int $pageNum = 1, int $pageSize = 10): LengthAwarePaginator
    {
        $query = AppLiveRoom::query()
            ->select([
                'room_id', 'room_title', 'room_cover', 'live_type', 'third_party_room_id',
                'anchor_name', 'scheduled_start_time', 'scheduled_end_time',
                'student_code', 'teacher_code', 'admin_code', 'mock_video_id', 'mock_room_id',
                'live_status', 'app_template', 'enable_live_sell', 'status', 'created_at',
            ])
            ->with('stat:room_id,current_online_count,total_viewer_count');

        // 按直播类型筛选
        if (isset($filters['liveType']) && $filters['liveType'] !== '') {
            $query->where('live_type', $filters['liveType']);
        }

        // 按直播状态筛选
        if (isset($filters['liveStatus']) && $filters['liveStatus'] !== '') {
            $query->where('live_status', $filters['liveStatus']);
        }

        // 按启用状态筛选
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        // 按标题关键词搜索
        if (!empty($filters['roomTitle'])) {
            $query->where('room_title', 'like', '%' . $filters['roomTitle'] . '%');
        }

        // 按主播名称搜索
        if (!empty($filters['anchorName'])) {
            $query->where('anchor_name', 'like', '%' . $filters['anchorName'] . '%');
        }

        // 按直播平台筛选
        if (!empty($filters['livePlatform'])) {
            $query->where('live_platform', $filters['livePlatform']);
        }

        // 按时间范围筛选
        if (!empty($filters['beginTime'])) {
            $query->where('created_at', '>=', $filters['beginTime']);
        }
        if (!empty($filters['endTime'])) {
            $query->where('created_at', '<=', $filters['endTime']);
        }

        $query->orderByDesc('room_id');

        return $query->paginate($pageSize, ['*'], 'pageNum', $pageNum);
    }

    /**
     * 获取创建直播用的点播视频分页列表。
     *
     * 业务规则：
     * 1. 仅返回“转码成功 + 有播放地址”的百家云视频，避免前端选择后无法开播；
     * 2. videoId 为精确匹配，name 为模糊匹配；
     * 3. 默认按创建时间倒序，保证最近同步的视频优先展示。
     *
     * @param array<string, mixed> $filters
     * @param int $pageNum 页码
     * @param int $pageSize 每页条数
     * @return LengthAwarePaginator
     */
    public function getMockVideoList(array $filters, int $pageNum = 1, int $pageSize = 10): LengthAwarePaginator
    {
        $query = AppVideoBaijiayun::query()
            ->select([
                'id', 'video_id', 'name', 'status', 'publish_status',
                'preface_url', 'play_url', 'length', 'created_at',
            ])
            ->where('status', AppVideoBaijiayun::STATUS_TRANSCODE_SUCCESS);

        // play_url 为空时无法作为伪直播素材，需在查询阶段剔除。
        $query->whereNotNull('play_url')
            ->where('play_url', '!=', '');

        if (isset($filters['videoId']) && $filters['videoId'] !== '') {
            $query->where('video_id', (int)$filters['videoId']);
        }

        if (!empty($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        $query->orderByDesc('created_at')->orderByDesc('id');

        return $query->paginate($pageSize, ['*'], 'pageNum', $pageNum);
    }

    /**
     * 获取直播间详情
     *
     * @param int $roomId
     * @return AppLiveRoom|null
     */
    public function getDetail(int $roomId)
    {
        return AppLiveRoom::query()
            ->with('stat')
            ->where('room_id', $roomId)
            ->first();
    }

    /**
     * 创建直播间
     *
     * 关键规则：
     * 1. 创建前先调用第三方平台生成直播房间；
     * 2. 创建成功后将 room_cover 等展示字段落库，供 App 列表封面兜底。
     *
     * @param array $data
     * @return AppLiveRoom
     */
    public function create(array $data): AppLiveRoom
    {
        $liveType = (int)($data['liveType'] ?? AppLiveRoom::LIVE_TYPE_REAL);

        $roomData = [
            'room_title'           => $data['roomTitle'],
            'room_cover'           => $data['roomCover'],
            'live_type'            => $liveType,
            'scheduled_start_time' => $data['scheduledStartTime'] ?? null,
            'scheduled_end_time'   => $data['scheduledEndTime'] ?? null,
            'live_status'          => AppLiveRoom::LIVE_STATUS_NOT_STARTED,
            'enable_live_sell'     => 2, // ppt 带货模版
        ];

        if ($liveType === AppLiveRoom::LIVE_TYPE_PSEUDO) {
            // 伪直播
            $roomData['video_url'] = $data['videoUrl'];
        }

        // 1. 先调用百家云服务创建房间
        $service = new BaijiayunLiveService();

        $thirdResult = $service->createRoom(
            $roomData['room_title'],
            $roomData['scheduled_start_time'],
            $roomData['scheduled_end_time'],
            [
                'enable_live_sell' => $roomData['enable_live_sell']
            ]
        );
        if (isset($thirdResult['success']) && $thirdResult['success']) {
            $roomData['third_party_room_id'] = $thirdResult['data']['room_id'] ?? 0;
            $roomData['student_code'] = $thirdResult['data']['student_code'] ?? 0;
            $roomData['admin_code'] = $thirdResult['data']['admin_code'] ?? 0;
            $roomData['teacher_code'] = $thirdResult['data']['teacher_code'] ?? 0;

            // 2. 创建房间成功后将有用的返回信息保存数据库

            $room = AppLiveRoom::create($roomData);

            // 初始化统计记录
//            AppLiveRoomStat::create([
//                'room_id' => $room->room_id,
//            ]);

            Log::info('直播间创建成功', [
                'room_id'    => $room->room_id,
                'live_type'  => $liveType,
                'room_title' => $room->room_title,
            ]);
            return $room;

        }
        throw new \Exception('创建房间失败');
    }

    /**
     * 按直播类型创建直播间。
     *
     * 关键规则：
     * 1. 真实直播与伪直播统一走百家云建房流程，避免分支实现漂移；
     * 2. 伪直播仅按 mockVideoSource 透传一种素材参数，避免 mock_room_id 与 mock_video_id 同时生效；
     * 3. 主播模式会忽略 mock 字段，不落库也不透传第三方。
     *
     * 失败策略：
     * - 第三方建房失败：返回错误结构，不抛异常中断控制器流程；
     * - 数据库写入失败：记录日志并返回错误结构，交由控制器统一返回业务错误。
     *
     * @param array<string, mixed> $data
     * @return array{success:bool,error:string,data:array<string,mixed>}
     */
    public function createByType(array $data): array
    {
        $liveType = (int)($data['liveType'] ?? AppLiveRoom::LIVE_TYPE_REAL);
        $roomTitle = (string)($data['roomTitle'] ?? '');
        $scheduledStartTime = (string)($data['scheduledStartTime'] ?? '');
        $scheduledEndTime = (string)($data['scheduledEndTime'] ?? '');
        $enableLiveSell = (int)($data['enableLiveSell'] ?? 0);
        $appTemplate = (int)($data['app_template'] ?? 1);

        if ($roomTitle === '' || $scheduledStartTime === '' || $scheduledEndTime === '') {
            return [
                'success' => false,
                'error'   => '创建直播间参数不完整',
                'data'    => [],
            ];
        }

        if (!in_array($liveType, [AppLiveRoom::LIVE_TYPE_REAL, AppLiveRoom::LIVE_TYPE_PSEUDO], true)) {
            return [
                'success' => false,
                'error'   => '直播类型值无效',
                'data'    => [],
            ];
        }

        $createRoomOptions = [
            'enable_live_sell' => $enableLiveSell,
            'app_template'     => $appTemplate
        ];

        $roomData = [
            'room_title'           => $roomTitle,
            'room_cover'           => $data['roomCover'] ?? '',
            'room_intro'           => $data['roomIntro'] ?? '',
            'live_type'            => $liveType,
            'anchor_name'          => $data['anchorName'] ?? '',
            'anchor_avatar'        => $data['anchorAvatar'] ?? null,
            'scheduled_start_time' => $scheduledStartTime,
            'scheduled_end_time'   => $scheduledEndTime,
            'live_status'          => AppLiveRoom::LIVE_STATUS_NOT_STARTED,
            'enable_live_sell'     => $enableLiveSell,
            'app_template'         => $appTemplate
        ];

        if ($liveType === AppLiveRoom::LIVE_TYPE_PSEUDO) {
            $mockVideoSource = (int)($data['mockVideoSource'] ?? 0);
            if (!in_array($mockVideoSource, [1, 2], true)) {
                return [
                    'success' => false,
                    'error'   => '伪直播视频来源值无效',
                    'data'    => [],
                ];
            }

            $createRoomOptions['is_mock_live'] = 1;
            $roomData['mock_video_source'] = $mockVideoSource;

            if ($mockVideoSource === 1) {
                $mockRoomId = isset($data['mockRoomId']) ? (int)$data['mockRoomId'] : 0;
                if ($mockRoomId <= 0) {
                    return [
                        'success' => false,
                        'error'   => '伪直播关联的回放教室号无效',
                        'data'    => [],
                    ];
                }

                $createRoomOptions['mock_room_id'] = $mockRoomId;
                $roomData['mock_room_id'] = $mockRoomId;
                // 回放模式只落 mock_room_id，避免残留历史 mock_video_id 污染数据语义。
                $roomData['mock_video_id'] = null;
            } else {
                $mockVideoId = isset($data['mockVideoId']) ? (int)$data['mockVideoId'] : 0;
                if ($mockVideoId <= 0) {
                    return [
                        'success' => false,
                        'error'   => '伪直播视频ID无效',
                        'data'    => [],
                    ];
                }

                $createRoomOptions['mock_video_id'] = $mockVideoId;
                $roomData['mock_video_id'] = $mockVideoId;
                // 点播模式只落 mock_video_id，避免与 mock_room_id 冲突。
                $roomData['mock_room_id'] = null;
            }
        }

        $service = new BaijiayunLiveService();
        $createRoomResult = $service->createRoom(
            $roomTitle,
            $scheduledStartTime,
            $scheduledEndTime,
            $createRoomOptions
        );

        if (empty($createRoomResult['success']) || empty($createRoomResult['data'])) {
            return [
                'success' => false,
                'error'   => sprintf(
                    '直播间[%s]创建失败,错误原因: %s',
                    $roomTitle,
                    (string)($createRoomResult['error_message'] ?? '未知错误')
                ),
                'data'    => [],
            ];
        }

        $roomData['third_party_room_id'] = $createRoomResult['data']['room_id'] ?? 0;
        $roomData['student_code'] = $createRoomResult['data']['student_code'] ?? 0;
        $roomData['admin_code'] = $createRoomResult['data']['admin_code'] ?? 0;
        $roomData['teacher_code'] = $createRoomResult['data']['teacher_code'] ?? 0;

        try {
            $room = AppLiveRoom::query()->create($roomData);

            Log::info('直播间创建成功', [
                'room_id'    => $room->room_id,
                'live_type'  => $liveType,
                'room_title' => $roomTitle,
            ]);

            return [
                'success' => true,
                'error'   => '',
                'data'    => $createRoomResult['data'],
            ];
        } catch (\Exception $e) {
            Log::error('直播间入库失败', [
                'room_title' => $roomTitle,
                'live_type'  => $liveType,
                'error'      => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error'   => sprintf('直播间[%s]创建失败,错误原因: %s', $roomTitle, $e->getMessage()),
                'data'    => [],
            ];
        }
    }

    /**
     * 创建伪直播房间（兼容旧入口）。
     *
     * @param array $data
     * @return array{success:bool,error:string,data:array<string,mixed>}
     * @deprecated 建议使用 createByType()，该方法仅保留历史调用兼容。
     *
     */
    public function createMockRoom(array $data): array
    {
        // 兼容旧调用：未显式传来源时按历史行为默认走点播视频ID。
        $data['liveType'] = AppLiveRoom::LIVE_TYPE_PSEUDO;
        $data['enableLiveSell'] = $data['enableLiveSell'] ?? 0;
        $data['mockVideoSource'] = $data['mockVideoSource'] ?? (isset($data['mockRoomId']) ? 1 : 2);

        return $this->createByType($data);
    }

    /**
     * 更新直播间
     *
     * @param int $roomId
     * @param array $data
     * @return array ['success' => bool, 'error' => ?string]
     */
    public function update(int $roomId, array $data): array
    {
        $room = AppLiveRoom::query()->where('room_id', $roomId)->first();

        if (!$room) {
            return ['success' => false, 'error' => '直播间不存在'];
        }

        // 直播中禁止修改时间和推拉流地址
        if ($room->isLiving()) {
            $restrictedFields = ['scheduledStartTime', 'scheduledEndTime', 'pushUrl', 'pullUrl', 'videoUrl'];
            foreach ($restrictedFields as $field) {
                if (array_key_exists($field, $data) && $data[$field] !== null) {
                    return ['success' => false, 'error' => '直播进行中，无法修改'];
                }
            }
        }

        $fieldMap = [
            'roomTitle'          => 'room_title',
            'roomCover'          => 'room_cover',
            'roomIntro'          => 'room_intro',
            'videoUrl'           => 'video_url',
            'anchorName'         => 'anchor_name',
            'anchorAvatar'       => 'anchor_avatar',
            'scheduledStartTime' => 'scheduled_start_time',
            'scheduledEndTime'   => 'scheduled_end_time',
            'liveDuration'       => 'live_duration',
            'allowChat'          => 'allow_chat',
            'allowGift'          => 'allow_gift',
            'allowLike'          => 'allow_like',
            'password'           => 'password',
            'extConfig'          => 'ext_config',
            'status'             => 'status',
            'pushUrl'            => 'push_url',
            'pullUrl'            => 'pull_url',
        ];

        $updateData = [];
        foreach ($fieldMap as $inputKey => $dbKey) {
            if (array_key_exists($inputKey, $data)) {
                $updateData[$dbKey] = $data[$inputKey];
            }
        }

        if (!empty($updateData)) {
            $room->update($updateData);
        }

        return ['success' => true, 'error' => null];
    }

    /**
     * 删除直播间-不支持批量删除
     * 软删除
     *
     * @param int $roomId
     * @return array ['success' => bool, 'deleted' => int, 'error' => ?string]
     */
    public function delete(int $roomId): array
    {
        $room = AppLiveRoom::query()
            ->select(['room_id', 'third_party_room_id', 'live_status'])
            ->where('room_id', $roomId)
            ->first();

        if (!$room) {
            return ['success' => false, 'deleted' => 0, 'error' => '直播间不存在'];
        }

        if ($room->isLiving()) {
            return [
                'success' => false,
                'deleted' => 0,
                'error'   => '直播间"' . $room->room_title . '"正在直播中，无法删除',
            ];
        }

        $deleted = $room->delete() ? 1 : 0;

        try {
            // 实例化百家云服务类调用接口删除房间
            $service = new BaijiayunLiveService();
            $result = $service->deleteRoom($room['third_party_room_id']);
            // TODO 完善对 result 的处理
        } catch (\Exception $e) {

        }

        Log::info('直播间删除成功', [
            'room_id' => $roomId,
            'deleted' => $deleted
        ]);

        return ['success' => true, 'deleted' => $deleted, 'error' => null];
    }

    /**
     * 检查直播间是否被直播课程章节使用
     *
     * @param int $roomId
     * @return bool
     */
    public function isUsedByLiveCourseChapter(int $roomId): bool
    {
        return DB::table('app_chapter_content_live as l')
            ->join('app_course_chapter as ch', 'ch.chapter_id', '=', 'l.chapter_id')
            ->join('app_course_base as c', 'c.course_id', '=', 'ch.course_id')
            ->whereNull('l.deleted_at')
            ->whereNull('ch.deleted_at')
            ->whereNull('c.deleted_at')
            ->where('c.play_type', AppCourseBase::PLAY_TYPE_LIVE)
            ->where(function ($query) use ($roomId) {
                $query->where('l.live_room_id', $roomId);
            })
            ->exists();
    }

    /**
     * 修改直播间启用状态
     *
     * @param int $roomId
     * @param int $status
     * @return bool
     */
    public function changeStatus(int $roomId, int $status): bool
    {
        return AppLiveRoom::query()
                   ->where('room_id', $roomId)
                   ->update(['status' => $status]) > 0;
    }

    /**
     * 发送直播红包
     *
     * @param array $data
     * @param SystemUser|null $operator
     * @return array ['success' => bool, 'error' => ?string, 'data' => array]
     */
    public function sendRedPacket(array $data, ?SystemUser $operator = null): array
    {
        $roomId = (int)$data['roomId'];
        $room = AppLiveRoom::query()->where('room_id', $roomId)->first();
        if (!$room) {
            return ['success' => false, 'error' => '直播间不存在', 'data' => []];
        }

        if ((int)$room->status !== AppLiveRoom::STATUS_ENABLED) {
            return ['success' => false, 'error' => '直播间已禁用', 'data' => []];
        }

        if ((int)$room->live_status !== AppLiveRoom::LIVE_STATUS_LIVING) {
            return ['success' => false, 'error' => '直播未开始，无法发送红包', 'data' => []];
        }

        if ((int)$room->allow_gift !== 1) {
            return ['success' => false, 'error' => '该直播间未开启送礼功能', 'data' => []];
        }

        $senderId = $operator ? (int)$operator->user_id : 0;
        $senderName = $operator
            ? (string)($operator->nick_name ?: $operator->user_name)
            : '系统';

        $totalAmount = number_format((float)$data['totalAmount'], 2, '.', '');
        $packetCount = (int)$data['packetCount'];
        $expireSeconds = (int)$data['expireSeconds'];
        $title = (string)$data['title'];
        $content = (string)$data['content'];

        $extData = [
            'title'         => $title,
            'totalAmount'   => $totalAmount,
            'packetCount'   => $packetCount,
            'expireSeconds' => $expireSeconds,
        ];
        if (!empty($data['extra']) && is_array($data['extra'])) {
            $extData['extra'] = $data['extra'];
        }

        $message = null;
        DB::transaction(function () use ($roomId, $senderName, $content, $extData, $packetCount, $totalAmount, &$message) {
            $message = AppLiveChatMessage::query()->create([
                'room_id'       => $roomId,
                'member_id'     => 0,
                'member_name'   => $senderName,
                'member_avatar' => null,
                'message_type'  => AppLiveChatMessage::TYPE_RED_PACKET,
                'content'       => $content,
                'ext_data'      => $extData,
                'is_top'        => 0,
                'is_blocked'    => 0,
            ]);

            AppLiveRoomStat::query()->firstOrCreate(
                ['room_id' => $roomId],
                [
                    'total_viewer_count'   => 0,
                    'max_online_count'     => 0,
                    'current_online_count' => 0,
                    'like_count'           => 0,
                    'message_count'        => 0,
                    'gift_count'           => 0,
                    'gift_amount'          => 0,
                    'share_count'          => 0,
                    'avg_watch_duration'   => 0,
                ]
            );

            AppLiveRoomStat::query()
                ->where('room_id', $roomId)
                ->update([
                    'message_count' => DB::raw('message_count + 1'),
                    'gift_count'    => DB::raw('gift_count + ' . $packetCount),
                    'gift_amount'   => DB::raw('gift_amount + ' . $totalAmount),
                    'updated_at'    => now(),
                ]);
        });

        event(new LiveRedPacketSent(
            $roomId,
            (int)$message->message_id,
            $content,
            $extData,
            [
                'type' => 'admin',
                'id'   => $senderId,
                'name' => $senderName,
            ],
            $message->created_at ? $message->created_at->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s')
        ));

        return [
            'success' => true,
            'error'   => null,
            'data'    => [
                'messageId' => (int)$message->message_id,
                'roomId'    => $roomId,
                'event'     => 'live.red_packet.sent',
                'createdAt' => $message->created_at ? $message->created_at->format('Y-m-d H:i:s') : null,
            ],
        ];
    }
}
