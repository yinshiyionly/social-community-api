<?php

namespace App\Services\Admin;

use App\Models\App\AppLiveRoom;
use App\Models\App\AppLiveRoomStat;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

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
                'room_id', 'room_title', 'room_cover', 'live_type', 'live_platform',
                'anchor_name', 'scheduled_start_time', 'scheduled_end_time',
                'live_status', 'allow_chat', 'allow_gift', 'status', 'created_at',
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
     * @param array $data
     * @return AppLiveRoom
     */
    public function create(array $data): AppLiveRoom
    {
        $liveType = (int) ($data['liveType'] ?? AppLiveRoom::LIVE_TYPE_REAL);

        $roomData = [
            'room_title'          => $data['roomTitle'],
            'room_cover'          => $data['roomCover'] ?? null,
            'room_intro'          => $data['roomIntro'] ?? '',
            'live_type'           => $liveType,
            'anchor_name'         => $data['anchorName'] ?? '',
            'anchor_avatar'       => $data['anchorAvatar'] ?? null,
            'scheduled_start_time'=> $data['scheduledStartTime'] ?? null,
            'scheduled_end_time'  => $data['scheduledEndTime'] ?? null,
            'live_duration'       => $data['liveDuration'] ?? 0,
            'live_status'         => AppLiveRoom::LIVE_STATUS_NOT_STARTED,
            'allow_chat'          => $data['allowChat'] ?? 1,
            'allow_gift'          => $data['allowGift'] ?? 0,
            'allow_like'          => $data['allowLike'] ?? 1,
            'password'            => $data['password'] ?? null,
            'ext_config'          => $data['extConfig'] ?? [],
            'status'              => $data['status'] ?? AppLiveRoom::STATUS_ENABLED,
        ];

        if ($liveType === AppLiveRoom::LIVE_TYPE_PSEUDO) {
            // 伪直播
            $roomData['live_platform'] = AppLiveRoom::PLATFORM_CUSTOM;
            $roomData['video_url'] = $data['videoUrl'];
        } else {
            // 真实直播/主播模式
            $roomData['live_platform'] = $data['livePlatform'] ?? AppLiveRoom::PLATFORM_CUSTOM;
            $roomData['push_url'] = $data['pushUrl'] ?? null;
            $roomData['pull_url'] = $data['pullUrl'] ?? null;
            $roomData['video_url'] = $data['videoUrl'] ?? null;
        }

        $room = AppLiveRoom::create($roomData);

        // 初始化统计记录
        AppLiveRoomStat::create([
            'room_id' => $room->room_id,
        ]);

        Log::info('直播间创建成功', [
            'room_id' => $room->room_id,
            'live_type' => $liveType,
            'room_title' => $room->room_title,
        ]);

        return $room;
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
     * 删除直播间（支持批量，软删除）
     *
     * @param array $roomIds
     * @return array ['success' => bool, 'deleted' => int, 'error' => ?string]
     */
    public function delete(array $roomIds): array
    {
        $rooms = AppLiveRoom::query()
            ->whereIn('room_id', $roomIds)
            ->get();

        if ($rooms->isEmpty()) {
            return ['success' => false, 'deleted' => 0, 'error' => '直播间不存在'];
        }

        // 检查是否有直播中的直播间
        foreach ($rooms as $room) {
            if ($room->isLiving()) {
                return [
                    'success' => false,
                    'deleted' => 0,
                    'error' => '直播间"' . $room->room_title . '"正在直播中，无法删除',
                ];
            }
        }

        $deleted = AppLiveRoom::query()
            ->whereIn('room_id', $roomIds)
            ->delete();

        Log::info('直播间删除成功', [
            'room_ids' => $roomIds,
            'deleted' => $deleted,
        ]);

        return ['success' => true, 'deleted' => $deleted, 'error' => null];
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
}
