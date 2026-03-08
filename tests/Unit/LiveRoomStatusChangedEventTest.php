<?php

namespace Tests\Unit;

use App\Events\LiveRoomStatusChanged;
use Tests\TestCase;

class LiveRoomStatusChangedEventTest extends TestCase
{
    public function test_broadcast_contract()
    {
        $event = new LiveRoomStatusChanged(
            1001,
            1,
            'live.started',
            '2026-03-06 15:30:00',
            'baijiayun'
        );

        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertSame('live.room.1001', $channels[0]->name);
        $this->assertSame('live.status.changed', $event->broadcastAs());
        $this->assertSame([
            'eventType' => 'live.started',
            'roomId' => 1001,
            'liveStatus' => 1,
            'actualTime' => '2026-03-06 15:30:00',
            'source' => 'baijiayun',
        ], $event->broadcastWith());
    }
}
