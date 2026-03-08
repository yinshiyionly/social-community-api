<?php

namespace Tests\Unit;

use App\Events\LiveRedPacketSent;
use Tests\TestCase;

class LiveRedPacketSentEventTest extends TestCase
{
    public function test_broadcast_contract()
    {
        $event = new LiveRedPacketSent(
            1001,
            90001,
            '红包雨来啦',
            [
                'title' => '直播红包',
                'totalAmount' => '88.00',
                'packetCount' => 20,
                'expireSeconds' => 300,
            ],
            [
                'type' => 'admin',
                'id' => 12,
                'name' => '运营A',
            ],
            '2026-03-06 15:32:00'
        );

        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertSame('live.room.1001', $channels[0]->name);
        $this->assertSame('live.red_packet.sent', $event->broadcastAs());
        $this->assertSame([
            'eventType' => 'live.red_packet',
            'roomId' => 1001,
            'messageId' => 90001,
            'content' => '红包雨来啦',
            'extData' => [
                'title' => '直播红包',
                'totalAmount' => '88.00',
                'packetCount' => 20,
                'expireSeconds' => 300,
            ],
            'sender' => [
                'type' => 'admin',
                'id' => 12,
                'name' => '运营A',
            ],
            'createdAt' => '2026-03-06 15:32:00',
        ], $event->broadcastWith());
    }
}
