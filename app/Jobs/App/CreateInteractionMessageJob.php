<?php

namespace App\Jobs\App;

use App\Constant\MessageType;
use App\Models\App\AppMessageInteraction;
use App\Models\App\AppMessageUnreadCount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * 创建互动消息异步任务
 */
class CreateInteractionMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 任务最大尝试次数
     *
     * @var int
     */
    public $tries = 3;

    /**
     * 任务超时时间（秒）
     *
     * @var int
     */
    public $timeout = 30;

    /**
     * 重试延迟时间（秒）
     *
     * @var int
     */
    public $backoff = 5;

    /**
     * 消息数据
     *
     * @var array
     */
    protected $data;

    /**
     * Create a new job instance.
     *
     * @param array $data 消息数据
     */
    public function __construct(array $data)
    {
        $this->data = $data;

        // 设置队列名称
        $this->onQueue('user-message');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        Log::channel('job')->info('开始创建互动消息', [
            'job' => self::class,
            'data' => $this->maskData(),
            'attempt' => $this->attempts(),
        ]);

        try {
            // 不给自己发消息
            if ($this->data['sender_id'] === $this->data['receiver_id']) {
                Log::channel('job')->info('跳过自己给自己的消息', [
                    'job' => self::class,
                    'member_id' => $this->data['sender_id'],
                ]);
                return;
            }

            // 创建消息记录
            $message = AppMessageInteraction::create([
                'receiver_id' => $this->data['receiver_id'],
                'sender_id' => $this->data['sender_id'],
                'message_type' => $this->data['message_type'],
                'target_id' => $this->data['target_id'] ?? null,
                'target_type' => $this->data['target_type'] ?? null,
                'content_summary' => $this->data['content_summary'] ?? null,
                'cover_url' => $this->data['cover_url'] ?? null,
                'is_read' => AppMessageInteraction::READ_NO,
            ]);

            // 更新未读数
            $this->incrementUnreadCount($this->data['receiver_id'], $this->data['message_type']);

            Log::channel('job')->info('互动消息创建成功', [
                'job' => self::class,
                'message_id' => $message->message_id,
                'receiver_id' => $this->data['receiver_id'],
                'message_type' => $this->data['message_type'],
            ]);

        } catch (\Exception $e) {
            Log::channel('job')->error('创建互动消息失败', [
                'job' => self::class,
                'data' => $this->maskData(),
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * 增加未读数
     *
     * @param int $memberId
     * @param int $messageType
     * @return void
     */
    private function incrementUnreadCount(int $memberId, int $messageType): void
    {
        switch ($messageType) {
            case MessageType::LIKE:
                AppMessageUnreadCount::incrementLike($memberId);
                break;
            case MessageType::COLLECT:
                AppMessageUnreadCount::incrementCollect($memberId);
                break;
            case MessageType::COMMENT:
                AppMessageUnreadCount::incrementComment($memberId);
                break;
            case MessageType::FOLLOW:
                AppMessageUnreadCount::incrementFollow($memberId);
                break;
        }
    }

    /**
     * 处理任务失败
     *
     * @param Throwable $e
     * @return void
     */
    public function failed(Throwable $e): void
    {
        Log::channel('job')->error('互动消息任务最终失败', [
            'job' => self::class,
            'data' => $this->maskData(),
            'error' => $e->getMessage(),
        ]);
    }

    /**
     * 获取任务标签，用于 Horizon 监控
     *
     * @return array
     */
    public function tags(): array
    {
        return [
            'message',
            'interaction',
            'type:' . ($this->data['message_type'] ?? 0),
            'receiver:' . ($this->data['receiver_id'] ?? 0),
        ];
    }

    /**
     * 脱敏数据用于日志记录
     *
     * @return array
     */
    protected function maskData(): array
    {
        return [
            'sender_id' => $this->data['sender_id'] ?? null,
            'receiver_id' => $this->data['receiver_id'] ?? null,
            'message_type' => $this->data['message_type'] ?? null,
            'target_id' => $this->data['target_id'] ?? null,
            'target_type' => $this->data['target_type'] ?? null,
        ];
    }
}
