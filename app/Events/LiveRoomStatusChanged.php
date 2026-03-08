<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LiveRoomStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var int
     */
    public $roomId;

    /**
     * @var int
     */
    public $liveStatus;

    /**
     * @var string
     */
    public $eventType;

    /**
     * @var string
     */
    public $actualTime;

    /**
     * @var string
     */
    public $source;

    /**
     * @param int $roomId
     * @param int $liveStatus
     * @param string $eventType
     * @param string $actualTime
     * @param string $source
     */
    public function __construct(int $roomId, int $liveStatus, string $eventType, string $actualTime, string $source = 'baijiayun')
    {
        $this->roomId = $roomId;
        $this->liveStatus = $liveStatus;
        $this->eventType = $eventType;
        $this->actualTime = $actualTime;
        $this->source = $source;
    }

    /**
     * @return array
     */
    public function broadcastOn()
    {
        return [new Channel('live.room.' . $this->roomId)];
    }

    /**
     * @return string
     */
    public function broadcastAs()
    {
        return 'live.status.changed';
    }

    /**
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'eventType' => $this->eventType,
            'roomId' => $this->roomId,
            'liveStatus' => $this->liveStatus,
            'actualTime' => $this->actualTime,
            'source' => $this->source,
        ];
    }
}
