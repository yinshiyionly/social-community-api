<?php

namespace App\Jobs\App;

use App\Services\VolcengineSmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendLoginSmsCodeJob implements ShouldQueue, ShouldBeUnique
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
    public $backoff = 10;

    /**
     * 唯一锁的过期时间（秒）
     * 防止任务失败后锁永久存在
     *
     * @var int
     */
    public $uniqueFor = 300;

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
        $this->onQueue('sms');
    }

    /**
     * 获取任务的唯一标识，防止重复任务
     *
     * @return string
     */
    public function uniqueId(): string
    {
        if (!empty($this->params['phone_number']) && !empty($this->params['template_id'])) {
            return $this->params['phone_number'] . '^' . $this->params['template_id'];
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
        Log::channel('job')->info('[登录短信验证码发送队列开始]', [
            'params' => $this->maskParams(),
            'attempt' => $this->attempts()
        ]);

        try {
            // 0. 检查参数
            $this->validateParams();

            // 1. 发送短信
            $smsService = new VolcengineSmsService();
            $result = $smsService->sendVerificationCode(
                $this->params['phone_number'],
                $this->params['code'],
                $this->params['template_id'],
                $this->params['expire_minutes'] ?? 5
            );

            // 2. 检查发送结果
            if (!$result['success']) {
                $msg = sprintf(
                    '[登录短信验证码发送失败]: %s (code: %s)',
                    $result['error_message'] ?? 'Unknown error',
                    $result['error_code'] ?? 'Unknown'
                );

                Log::channel('job')->error($msg, [
                    'params' => $this->maskParams(),
                    'attempt' => $this->attempts(),
                    'result' => $result
                ]);

                throw new \Exception($msg);
            }

            Log::channel('job')->info('[登录短信验证码发送成功]', [
                'params' => $this->maskParams(),
                'attempt' => $this->attempts(),
                'request_id' => $result['request_id'] ?? null
            ]);

            return true;

        } catch (\Exception $e) {
            $msg = '[登录短信验证码发送队列失败]: ' . $e->getMessage();

            Log::channel('job')->error($msg, [
                'params' => $this->maskParams(),
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
        Log::error('[登录短信验证码发送最终失败]', [
            'params' => $this->maskParams(),
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
            'sms',
            'login-code',
            'phone:' . $this->maskPhone($this->params['phone_number'] ?? ''),
            'template:' . ($this->params['template_id'] ?? ''),
        ];
    }

    /**
     * 验证参数
     *
     * @throws \Exception
     */
    protected function validateParams()
    {
        if (empty($this->params['phone_number'])) {
            throw new \Exception('缺少手机号参数');
        }

        if (empty($this->params['code'])) {
            throw new \Exception('缺少验证码参数');
        }

        if (empty($this->params['template_id'])) {
            throw new \Exception('缺少模板ID参数');
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
            'phone_number' => $this->maskPhone($this->params['phone_number'] ?? ''),
            'template_id' => $this->params['template_id'] ?? '',
            'expire_minutes' => $this->params['expire_minutes'] ?? 5,
        ];
    }

    /**
     * 手机号脱敏
     *
     * @param string $phone
     * @return string
     */
    protected function maskPhone(string $phone): string
    {
        if (strlen($phone) >= 7) {
            return substr($phone, 0, 3) . '****' . substr($phone, -4);
        }

        return '***';
    }
}
