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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * 创建互动消息异步任务。
 *
 * 触发来源：
 * - App 端点赞/收藏/评论/关注等互动行为通过 MessageService 分发到该队列任务。
 *
 * 影响范围：
 * 1. 写入 app_message_interaction 消息记录；
 * 2. 更新 app_message_unread_count 对应类型未读数；
 * 3. 关注消息在写入层按 receiver_id + sender_id 做幂等，重复关注刷新时间并重算 follow_count。
 *
 * 队列与重试策略：
 * - 队列名：user-message；
 * - 可恢复失败（例如数据库短暂抖动）通过抛异常触发最多 3 次重试。
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
     * 执行互动消息写入任务。
     *
     * 失败策略：
     * - 发生异常时记录 job 日志并继续抛出，交由队列系统按重试配置重试；
     * - 对不可恢复场景不吞异常，避免未读计数与消息记录长期不一致。
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

            $messageType = (int) ($this->data['message_type'] ?? 0);

            // 关注消息在写入层做幂等：同一 receiver + sender 仅保留一条记录，并刷新最新关注时间。
            if ($messageType === MessageType::FOLLOW) {
                $message = $this->upsertFollowMessage();

                Log::channel('job')->info('互动消息创建成功', [
                    'job' => self::class,
                    'message_id' => $message->message_id,
                    'receiver_id' => $message->receiver_id,
                    'message_type' => $messageType,
                ]);

                return;
            }

            // 创建消息记录
            $message = AppMessageInteraction::create([
                'receiver_id' => $this->data['receiver_id'],
                'sender_id' => $this->data['sender_id'],
                'message_type' => $messageType,
                'target_id' => $this->data['target_id'] ?? null,
                'target_type' => $this->data['target_type'] ?? null,
                'content_summary' => $this->data['content_summary'] ?? null,
                'cover_url' => $this->data['cover_url'] ?? null,
                'is_read' => AppMessageInteraction::READ_NO,
            ]);

            // 更新未读数
            $this->incrementUnreadCount((int) $this->data['receiver_id'], $messageType);

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
     * 关注消息幂等写入。
     *
     * 规则：
     * 1. 幂等键为 receiver_id + sender_id（仅 message_type=FOLLOW）；
     * 2. 命中已存在记录时强制改为未读，并刷新 created_at/updated_at；
     * 3. follow_count 以库内未读关注消息实时计数回写，避免同发送者重复累加。
     *
     * @return AppMessageInteraction
     */
    private function upsertFollowMessage(): AppMessageInteraction
    {
        $receiverId = (int) ($this->data['receiver_id'] ?? 0);
        $senderId = (int) ($this->data['sender_id'] ?? 0);
        $now = now()->format('Y-m-d H:i:s');

        return DB::transaction(function () use ($receiverId, $senderId, $now) {
            DB::insert(
                "INSERT INTO app_message_interaction (
                    receiver_id,
                    sender_id,
                    message_type,
                    target_id,
                    target_type,
                    content_summary,
                    cover_url,
                    is_read,
                    created_at,
                    updated_at
                ) VALUES (?, ?, ?, NULL, NULL, NULL, NULL, ?, ?, ?)
                ON CONFLICT (receiver_id, sender_id) WHERE message_type = 4
                DO UPDATE SET
                    is_read = EXCLUDED.is_read,
                    created_at = EXCLUDED.created_at,
                    updated_at = EXCLUDED.updated_at",
                [
                    $receiverId,
                    $senderId,
                    MessageType::FOLLOW,
                    AppMessageInteraction::READ_NO,
                    $now,
                    $now,
                ]
            );

            $this->syncFollowUnreadCount($receiverId);

            $message = AppMessageInteraction::query()
                ->where('receiver_id', $receiverId)
                ->where('sender_id', $senderId)
                ->where('message_type', MessageType::FOLLOW)
                ->first();

            if (!$message) {
                throw new \RuntimeException('关注消息幂等写入后未找到记录');
            }

            return $message;
        });
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
        }
    }

    /**
     * 同步关注未读数。
     *
     * @param int $memberId
     * @return void
     */
    private function syncFollowUnreadCount(int $memberId): void
    {
        $followUnreadCount = AppMessageInteraction::query()
            ->where('receiver_id', $memberId)
            ->where('message_type', MessageType::FOLLOW)
            ->where('is_read', AppMessageInteraction::READ_NO)
            ->count();

        AppMessageUnreadCount::getOrCreate($memberId)->update([
            'follow_count' => $followUnreadCount,
        ]);
    }

    /**
     * 处理任务最终失败。
     *
     * 当前策略：
     * - 无自动补偿，仅记录错误日志，便于告警与人工排查。
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
