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

class ProcessPointConsumeJob implements ShouldQueue
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
        Log::channel('job')->info('[积分消费任务开始]', [
            'job' => self::class,
            'params' => $this->maskParams(),
            'attempt' => $this->attempts(),
        ]);

        try {
            $this->validateParams();

            $pointService = new PointService();
            $result = $pointService->processConsume(
                $this->params['member_id'],
                $this->params['points'],
                $this->params['title'],
                $this->params['order_no'] ?? null,
                $this->params['remark'] ?? null,
                $this->params['client_ip'] ?? null
            );

            if ($result['success']) {
                Log::channel('job')->info('[积分消费任务成功]', [
                    'job' => self::class,
                    'params' => $this->maskParams(),
                    'result' => $result,
                ]);
            } else {
                Log::channel('job')->warning('[积分消费任务未执行]', [
                    'job' => self::class,
                    'params' => $this->maskParams(),
                    'reason' => $result['message'],
                ]);
            }
        } catch (\Exception $e) {
            Log::channel('job')->error('[积分消费任务失败]', [
                'job' => self::class,
                'params' => $this->maskParams(),
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
        Log::channel('job')->error('[积分消费任务最终失败]', [
            'job' => self::class,
            'params' => $this->maskParams(),
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
            'consume',
            'member:' . ($this->params['member_id'] ?? 0),
            'order:' . ($this->params['order_no'] ?? ''),
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

        if (empty($this->params['points']) || $this->params['points'] <= 0) {
            throw new \Exception('消费积分数必须大于0');
        }

        if (empty($this->params['title'])) {
            throw new \Exception('缺少消费标题参数');
        }
    }

    /**
     * 脱敏参数用于日志记录
     *
     * @return array
     */
    protected function maskParams(): array
    {
        return [
            'member_id' => $this->params['member_id'] ?? 0,
            'points' => $this->params['points'] ?? 0,
            'title' => $this->params['title'] ?? '',
            'order_no' => $this->params['order_no'] ?? '',
        ];
    }
}
