<?php

namespace App\Jobs\Complaint;


use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ComplaintDefamationSendMailJob implements ShouldQueue, ShouldBeUnique
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
    public $timeout = 300;

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
    public $backoff = 60;

    protected array $params;

    /**
     * Create a new job instance.
     *
     * @param array $params
     */
    public function __construct(array $params)
    {
        $this->params = $params;
        // 设置队列名称，便于 Horizon 监控和管理
        $this->onQueue('complaint-defamation');
    }

    /**
     * 唯一锁的过期时间（秒）
     * 防止任务失败后锁永久存在
     *
     * @var int
     */
    public $uniqueFor = 3600;

    /**
     * 获取任务的唯一标识，防止重复任务
     *
     * @return string
     */
    public function uniqueId(): string
    {
        // 使用 complaint_id 和 recipient_email 拼接
        if (!empty($this->params['complaint_id']) && isset($this->params['recipient_email'])) {
            return $this->params['complaint_id'] . '^' . $this->params['recipient_email'];
        }
        return '';
    }

    /**
     * Execute the job.
     *
     * @throws \Exception
     */
    public function handle()
    {
        Log::channel('job')->info('[诽谤类举报邮件发送队列开始]', [
            'params' => $this->params ?? [],
            'attempt' => $this->attempts()
        ]);
        try {
            // 0. 检查参数
            $this->validateParams();

            // 1. 实例化文件上传服务类
            $fileService = new \App\Services\FileUploadService();
            // 2. 实例化诽谤类举报服务类
            $service = new \App\Services\Complaint\ComplaintDefamationService($fileService);
            // 3. 发送邮件
            $service->sendEmail((int)$this->params['complaint_id'], $this->params['recipient_email']);

            return true;
        } catch (\Exception $e) {
            $msg = '[诽谤类举报邮件发送队列失败]: ' . $e->getMessage();
            Log::channel('job')->error($msg, [
                'params' => $this->params ?? [],
                'attempt' => $this->attempts(),
                'msg' => $e->getMessage()
            ]);
            throw new \Exception($msg);
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
        Log::error('[诽谤类举报邮件发送失败]', [
            'params' => $this->params ?? [],
            'attempt' => $this->attempts(),
            'msg' => $e->getMessage()
        ]);

        // 这里可以添加失败通知逻辑，比如发送邮件、钉钉通知等
    }

    /**
     * 获取任务标签，用于 Horizon 监控
     *
     * @return array
     */
    public function tags(): array
    {
        return [
            'complaint-defamation',
            'complaint_id:' . $this->params['complaint_id'],
            'recipient_email:' . $this->params['recipient_email']
        ];
    }

    /**
     * @throws \Exception
     */
    protected function validateParams()
    {
        if (empty($this->params['complaint_id']) || !isset($this->params['recipient_email'])) {
            throw new \Exception('队列事件缺少参数');
        }
    }
}
