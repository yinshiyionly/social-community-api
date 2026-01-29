<?php

namespace App\Jobs\App;

use App\Services\App\PointService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessPointEarnJob implements ShouldQueue
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
    public $timeout = 60;

    /**
     * 任务失败前的最大异常次数
     *
     * @var int
     */
    public $maxExceptions = 3;

    /**
     * 重试延迟时间（秒）
     *
     * @var int
     */
    public $backoff = 5;

    /**
     * @var array
     */
    protected $params;

    /**
     * Create a new job instance.
     *
     * @param array $params
     */
    public function __construct(array $params)
    {
        $this->params = $params;
        $this->onQueue('app-point');
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        Log::channel('job')->info('[积分获取任务开始]', [
            'job' => self::class,
            'params' => $this->params,
            'attempt' => $this->attempts(),
        ]);

        try {
            $this->validateParams();

            $pointService = new PointService();
            $result = $pointService->processTaskEarn(
                $this->params['member_id'],
                $this->params['task_code'],
                $this->params['biz_id'] ?? null,
                $this->params['client_ip'] ?? null
            );

            if ($result['success']) {
                Log::channel('job')->info('[积分获取任务成功]', [
                    'job' => self::class,
                    'params' => $this->params,
                    'result' => $result,
                ]);
            } else {
                Log::channel('job')->warning('[积分获取任务未执行]', [
                    'job' => self::class,
                    'params' => $this->params,
                    'reason' => $result['message'],
                ]);
            }
        } catch (\Exception $e) {
            Log::channel('job')->error('[积分获取任务失败]', [
                'job' => self::class,
                'params' => $this->params,
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
    public function failed(Throwable $e)
    {
        Log::channel('job')->error('[积分获取任务最终失败]', [
            'job' => self::class,
            'params' => $this->params,
            'error' => $e->getMessage(),
        ]);
    }

    /**
     * 获取任务标签
     *
     * @return array
     */
    public function tags(): array
    {
        return [
            'point',
            'earn',
            'member:' . ($this->params['member_id'] ?? 0),
            'task:' . ($this->params['task_code'] ?? ''),
        ];
    }

    /**
     * 验证参数
     *
     * @throws \Exception
     */
    protected function validateParams(): void
    {
        if (empty($this->params['member_id'])) {
            throw new \Exception('缺少用户ID参数');
        }

        if (empty($this->params['task_code'])) {
            throw new \Exception('缺少任务编码参数');
        }
    }

//     $pointService = new PointService();

// // 异步触发（推荐）
// $pointService->triggerTaskEarn($memberId, 'daily_post', $postId);
// $pointService->triggerTaskEarn($memberId, 'first_post'); // 成长任务

// // 同步消费（需要即时结果）
// $result = $pointService->consumeSync($memberId, 100, '兑换商品', $orderNo);

}
