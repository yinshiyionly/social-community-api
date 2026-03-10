<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponse;
use App\Models\App\AppLiveRoom;
use App\Models\App\AppMemberBase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * 直播控制器
 */
class LiveController extends Controller
{
    /**
     * 获取当前登录会员ID
     *
     * @param Request $request
     * @return int
     */
    protected function getMemberId(Request $request): int
    {
        return $request->attributes->get('member_id');
    }

    /**
     * 获取直播间信息
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function roomInfo(Request $request)
    {
        // 1. 验证 roomId 参数 判断 room_id 是否存在
        $roomId = $request->input('roomId', 0);
        $roomInfo = AppLiveRoom::query()
            ->select(['room_id', 'room_title', 'room_cover', 'live_type', 'third_party_room_id', 'scheduled_start_time', 'scheduled_end_time'])
            ->enabled()
            ->where('room_id', $roomId)
            ->first();
        if (empty($roomInfo)) {
            return ApiResponse::error('直播数据错误');
        }

        // 2. 获取会员数据并验证
        $memberId = $this->getMemberId($request);
        Log::info('用户进入直播间', ['memberId' => $memberId]);


        $memberInfo = AppMemberBase::query()
            ->select(['member_id', 'phone', 'nickname', 'avatar', 'gender'])
            ->normal()
            ->where('member_id', $memberId)
            ->first();
        if (empty($memberInfo)) {
            return ApiResponse::error('会员数据错误');
        }
        return ApiResponse::success([
            'data' => [
                'roomId' => $roomInfo['room_id'],
                'thirdPartyRoomId' => $roomInfo['third_party_room_id'],
                'title' => $roomInfo['room_title'],
                'name' => $memberInfo['nickname'],
                'number' => $memberInfo['member_id'],
                'avatar' => $memberInfo['avatar'],
                'type' => 0, // 0=学生 1-老师 2-助教
                'groupId' => 0, // 分组号，默认0不分组，只有分组直播才用到，不分组则不需要传此参数
                'ws' => $this->buildWsConfig((int)$roomInfo['room_id']),
            ]
        ]);
    }

    /**
     * 进入直播间
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function enter(Request $request)
    {
        // 1. 验证 liveId 参数 判断 room_id 是否存在
        $roomId = $request->input('liveId', 0);
        $roomInfo = AppLiveRoom::query()
            ->select(['room_id', 'room_title', 'room_cover', 'live_type', 'third_party_room_id', 'scheduled_start_time', 'scheduled_end_time', 'student_code'])
            ->enabled()
            ->where('room_id', $roomId)
            ->first();
        if (empty($roomInfo)) {
            return ApiResponse::error('直播数据错误');
        }

        return ApiResponse::success([
            'data' => [
                'id' => $roomInfo['room_id'],
                'liveId' => $roomInfo['third_party_room_id'],
                'title' => $roomInfo['room_title'],
                'enterMode' => 'code',
                'classType' => 'sell',
                'code' => $roomInfo['student_code'],
                'userType' => 0, // 0=学生 1-老师 2-助教
                'groupId' => 0 // 分组号，默认0不分组，只有分组直播才用到，不分组则不需要传此参数
            ]
        ]);
    }

    /**
     * 直播 WS 配置
     *
     * @param int $roomId
     * @return array
     */
    protected function buildWsConfig(int $roomId): array
    {
        $connection = (array)config('broadcasting.connections.pusher', []);
        $options = (array)($connection['options'] ?? []);
        $scheme = (string)($options['scheme'] ?? 'http');
        $host = (string)($options['host'] ?? '127.0.0.1');
        $port = (int)($options['port'] ?? 6001);

        return [
            'enabled' => (string)($connection['key'] ?? '') !== '',
            'broadcaster' => 'pusher',
            'host' => $host,
            'port' => $port,
            'scheme' => $scheme,
            'key' => (string)($connection['key'] ?? ''),
            'channel' => 'live.room.' . $roomId,
            'events' => [
                'statusChanged' => 'live.status.changed',
                'redPacketSent' => 'live.red_packet.sent',
            ],
        ];
    }
}
