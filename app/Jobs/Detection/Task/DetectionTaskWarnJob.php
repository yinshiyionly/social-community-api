<?php

namespace App\Jobs\Detection\Task;

use App\Mail\Detection\Task\DetectionTaskWarnMail;
use App\Models\Detection\DetectionTaskMaster;
use App\Models\Insight\InsightPost;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class DetectionTaskWarnJob implements ShouldQueue, ShouldBeUnique
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
        $this->onQueue('detection-task');
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
        // 使用原始ID和sentiment拼接
        if (!empty($this->params['origin_id']) && isset($this->params['sentiment'])) {
            return $this->params['origin_id'] . '^' . $this->params['sentiment'];
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
        Log::channel('job')->info('[内容洞察负向数据预警通知队列开始]', [
            'params' => $this->params ?? [],
            'attempt' => $this->attempts()
        ]);
        try {
            // 0. 检查参数
            $this->validateParams();

            // 1. 根据 origin_id 获取数据详情
            $data = $this->getDataByOriginId();

            // 2. 再次确认是否为负向情绪数据
            if ($data['sentiment'] != 2) {
                Log::channel('job')->info('非负向情绪数据,跳过处理', [
                    'params' => $this->params
                ]);
                return true;
            }

            // 3. 根据 matched_task_ids 获取内部系统任务列表(多条)
            if (empty($data['matched_task_ids'])) {
                Log::channel('job')->info('未找到内部系统任务列表', [
                    'params' => $this->params,
                ]);
                return true;
            }
            $innerTaskList = $this->getInnerTaskListByMatchedTaskIds($data['matched_task_ids']);
            Log::channel('job')->info(
                sprintf('数据 origin_id: %s 在内部系统关联查询到 %d 条实时监测任务, 分别是: %s',
                    $this->params['origin_id'],
                    count($innerTaskList),
                    collect($data)->pluck('task_id')->implode(',')
                )
            );

            // 4. 循环处理 innerTaskList
            foreach ($innerTaskList as $item) {
                // 4.1 过滤未开启预警配置的数据
                if ($item['warn_publish_email_state'] == 2 && empty($item['warn_publish_email_config'])) {
                    Log::channel('job')->error('未开启预警邮箱配置', [
                        'params' => $this->params,
                        'task_id' => $item['task_id'] ?? '',
                        'task_name' => $item['task_name'] ?? '',
                        'warm_name' => $item['warn_name'] ?? '',
                        'warn_publish_email_state' => $item['warn_publish_email_state'],
                        'warn_publish_email_config' => $item['warn_publish_email_config'],
                    ]);
                    continue;
                }
                // 4.2 过滤未设置预警通知时间或不在预警通知有效时间内
                $now = Carbon::now();
                if (empty($item['warn_reception_start_time']) || empty($item['warn_reception_end_time'])
                    || !$now->between($item['warn_reception_start_time'], $item['warn_reception_end_time'])) {
                    Log::channel('job')->error('未设置预警通知时间或不在预警通知有效时间内', [
                        'params' => $this->params,
                        'task_id' => $item['task_id'] ?? '',
                        'task_name' => $item['task_name'] ?? '',
                        'warm_name' => $item['warn_name'] ?? '',
                        'warn_reception_start_time' => $item['warn_reception_start_time'],
                        'warn_reception_end_time' => $item['warn_reception_end_time']
                    ]);
                    continue;
                }
                // 4.3 执行邮箱通知逻辑
                $mailData = [
                    'task_name' => $item['task_name'] ?? '',
                    'warn_name' => $item['warn_name'] ?? '',
                    'origin_id' => $data['origin_id'] ?? '',
                    'title' => $data['title'] ?? '',
                    'url' => $data['url'] ?? '',
                    'publish_time' => !empty($data['publish_time'])
                        ? Carbon::make($data['publish_time'])->toDateTimeString()
                        : $now->toDateTimeString()
                ];
                $mailable = new DetectionTaskWarnMail('template1', $mailData);
                foreach ($item['warn_publish_email_config'] as $emailTo) {
                    // todo ->queue 使用队列异步发送
                    //      ->send  同步发送
                    Mail::to($emailTo)->queue($mailable);
                    Log::channel('job')->info('发送邮箱预警成功', [
                        'params' => $this->params,
                        'task_id' => $item['task_id'] ?? '',
                        'user_email' => $emailTo ?? '',
                        'task_name' => $item['task_name'] ?? '',
                        'warm_name' => $item['warn_name'] ?? ''
                    ]);
                }
            }
            return true;
        } catch (\Exception $e) {
            $msg = '[内容洞察负向数据预警通知队列失败]: ' . $e->getMessage();
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
        Log::error('[内容洞察负向数据预警通知失败]', [
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
            'detection-task',
            'origin_id:' . $this->params['origin_id'],
            'sentiment:' . $this->params['sentiment']
        ];
    }

    /**
     * @throws \Exception
     */
    protected function validateParams()
    {
        if (empty($this->params['origin_id']) || !isset($this->params['sentiment'])) {
            throw new \Exception('队列事件缺少参数');
        }
    }

    /**
     * 根据 origin_id 获取数据详情
     *
     * @return array
     * @throws \Exception
     */
    protected function getDataByOriginId(): array
    {
        $data = InsightPost::query()->where('origin_id', $this->params['origin_id'])->first();
        if (empty($data)) {
            $msg = sprintf('未找到origin_id=%s的数据', $this->params['origin_id']);
            Log::channel('job')->error($msg, [
                'params' => $this->params,
                'attempt' => $this->attempts()
            ]);
            throw new \Exception($msg);
        }
        return $data->toArray();
    }

    /**
     * 根据 matched_task_ids 获取内部系统的任务列表数据
     *
     * @param array $matchedTaskIds
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    protected function getInnerTaskListByMatchedTaskIds(array $matchedTaskIds)
    {
        $field = [
            'task_id',
            'external_task_id',
            'task_name',
            'warn_name',
            'warn_reception_start_time',
            'warn_reception_end_time',
            'warn_publish_email_state',
            'warn_publish_email_config',
            'warn_publish_wx_state',
            'warn_publish_wx_config',
            'status'
        ];
        return DetectionTaskMaster::query()
            ->select($field)
            ->whereIn('external_task_id', $matchedTaskIds)
            ->get()
            ->toArray();
    }
}
