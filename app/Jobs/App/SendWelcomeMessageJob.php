<?php

namespace App\Jobs\App;

use App\Models\App\AppMemberBase;
use App\Services\App\MessageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * 新用户注册后发送小秘书欢迎消息
 */
class SendWelcomeMessageJob implements ShouldQueue
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
     * 接收消息的用户ID
     *
     * @var int
     */
    protected $memberId;

    /**
     * Create a new job instance.
     *
     * @param int $memberId 新注册用户的 member_id
     */
    public function __construct(int $memberId)
    {
        $this->memberId = $memberId;
        $this->onQueue('system-message');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        Log::channel('job')->info('开始发送欢迎消息', [
            'job' => self::class,
            'member_id' => $this->memberId,
            'attempt' => $this->attempts(),
        ]);

        try {
            MessageService::createSystemMessage(
                AppMemberBase::SECRETARY_MEMBER_ID,
                $this->memberId,
                '欢迎加入社区',
                '欢迎加入社区'
            );

            Log::channel('job')->info('欢迎消息发送成功', [
                'job' => self::class,
                'member_id' => $this->memberId,
            ]);
        } catch (\Exception $e) {
            Log::channel('job')->error('欢迎消息发送失败', [
                'job' => self::class,
                'member_id' => $this->memberId,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
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
        Log::channel('job')->error('欢迎消息任务最终失败', [
            'job' => self::class,
            'member_id' => $this->memberId,
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
            'welcome',
            'member:' . $this->memberId,
        ];
    }
}
