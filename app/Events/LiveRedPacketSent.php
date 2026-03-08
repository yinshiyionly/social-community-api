<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LiveRedPacketSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var int
     */
    public $roomId;

    /**
     * @var int
     */
    public $messageId;

    /**
     * @var string
     */
    public $content;

    /**
     * @var array
     */
    public $extData;

    /**
     * @var array
     */
    public $sender;

    /**
     * @var string
     */
    public $createdAt;

    /**
     * @param int $roomId
     * @param int $messageId
     * @param string $content
     * @param array $extData
     * @param array $sender
     * @param string $createdAt
     */
    public function __construct(
        int $roomId,
        int $messageId,
        string $content,
        array $extData,
        array $sender,
        string $createdAt
    ) {
        $this->roomId = $roomId;
        $this->messageId = $messageId;
        $this->content = $content;
        $this->extData = $extData;
        $this->sender = $sender;
        $this->createdAt = $createdAt;
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
        return 'live.red_packet.sent';
    }

    /**
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'eventType' => 'live.red_packet',
            'roomId' => $this->roomId,
            'messageId' => $this->messageId,
            'content' => $this->content,
            'extData' => $this->extData,
            'sender' => $this->sender,
            'createdAt' => $this->createdAt,
        ];
    }
}
