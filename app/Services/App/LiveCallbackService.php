<?php

namespace App\Services\App;

use App\Events\LiveRoomStatusChanged;
use App\Models\App\AppChapterContentLive;
use App\Models\App\AppLiveRoom;
use App\Services\BaijiayunLiveService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LiveCallbackService
{
    /**
     * 处理百家云直播回调
     *
     * @param array $payload
     * @return array
     */
    public function handleBaijiayun(array $payload): array
    {
        $service = new BaijiayunLiveService();
        $verifyResult = $service->verifyCallback($payload);
        if (!$verifyResult['success']) {
            return [
                'code' => 600,
                'msg' => $verifyResult['error_message'] ?: 'callback verify failed',
            ];
        }

        $data = $verifyResult['data'] ?? [];
        $event = (string)($data['event'] ?? '');
        $thirdPartyRoomId = (string)($data['room_id'] ?? '');

        if ($thirdPartyRoomId === '' || !in_array($event, [
            BaijiayunLiveService::EVENT_LIVE_START,
            BaijiayunLiveService::EVENT_LIVE_END,
        ], true)) {
            return $this->successResponse();
        }

        $room = AppLiveRoom::query()
            ->where('third_party_room_id', $thirdPartyRoomId)
            ->first();
        if (!$room) {
            Log::warning('百家云直播回调未匹配到直播间', [
                'event' => $event,
                'third_party_room_id' => $thirdPartyRoomId,
            ]);
            return $this->successResponse();
        }

        $targetStatus = $event === BaijiayunLiveService::EVENT_LIVE_START
            ? AppLiveRoom::LIVE_STATUS_LIVING
            : AppLiveRoom::LIVE_STATUS_ENDED;

        if ((int)$room->live_status === $targetStatus) {
            Log::info('百家云直播回调重复，跳过处理', [
                'event' => $event,
                'room_id' => $room->room_id,
                'third_party_room_id' => $thirdPartyRoomId,
            ]);
            return $this->successResponse();
        }

        $now = now();
        DB::transaction(function () use ($room, $targetStatus, $event, $now, $thirdPartyRoomId) {
            $room->live_status = $targetStatus;
            if ($event === BaijiayunLiveService::EVENT_LIVE_START) {
                if (!$room->actual_start_time) {
                    $room->actual_start_time = $now;
                }
            } else {
                $room->actual_end_time = $now;
            }
            $room->save();

            $this->syncChapterLiveStatus(
                (int)$room->room_id,
                (string)$thirdPartyRoomId,
                $targetStatus,
                $event,
                $now->toDateTimeString()
            );
        });

        event(new LiveRoomStatusChanged(
            (int)$room->room_id,
            (int)$targetStatus,
            $event === BaijiayunLiveService::EVENT_LIVE_START ? 'live.started' : 'live.ended',
            $now->toDateTimeString(),
            'baijiayun'
        ));

        return $this->successResponse();
    }

    /**
     * 同步直播章节状态（兼容 room_id / live_room_id 双字段）
     *
     * @param int $roomId
     * @param string $thirdPartyRoomId
     * @param int $targetStatus
     * @param string $event
     * @param string $time
     * @return void
     */
    protected function syncChapterLiveStatus(
        int $roomId,
        string $thirdPartyRoomId,
        int $targetStatus,
        string $event,
        string $time
    ): void
    {
        $query = AppChapterContentLive::query()
            ->where(function ($builder) use ($roomId, $thirdPartyRoomId) {
                $builder->where('room_id', $roomId)
                    ->orWhere('live_room_id', (string)$roomId);

                if ($thirdPartyRoomId !== '') {
                    $builder->orWhere('live_room_id', $thirdPartyRoomId);
                }
            });

        $updateData = [
            'live_status' => $targetStatus,
        ];
        if ($event === BaijiayunLiveService::EVENT_LIVE_START) {
            $updateData['live_start_time'] = $time;
        } else {
            $updateData['live_end_time'] = $time;
        }

        $query->update($updateData);
    }

    /**
     * @return array
     */
    protected function successResponse(): array
    {
        return [
            'code' => 0,
            'msg' => 'ok',
        ];
    }
}
