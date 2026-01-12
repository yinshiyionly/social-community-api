<?php

namespace App\Jobs\App;

use App\Services\FileUploadService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class GenerateVideoCoverAndUploadTosJob implements ShouldQueue, ShouldBeUnique
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
    public $timeout = 120;

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
    public $backoff = 30;

    /**
     * 唯一锁的过期时间（秒）
     * 防止任务失败后锁永久存在
     *
     * @var int
     */
    public $uniqueFor = 600;

    protected array $params;

    /**
     * Create a new job instance.
     *
     * @param array $params
     *   - video_path: string 视频在 TOS 上的存储路径
     *   - video_url: string 视频完整 URL（可选，与 video_path 二选一）
     *   - snapshot_time: int 截图时间点，单位毫秒，默认 1000
     *   - format: string 截图格式，支持 jpg/png，默认 jpg
     *   - mode: string 截图模式，fast(关键帧)/accurate(精确)，默认 fast
     *   - cover_path: string 封面存储路径（可选，不传则自动生成）
     *   - callback: callable|null 回调函数，用于更新业务数据
     */
    public function __construct(array $params)
    {
        $this->params = $params;

        // 设置队列名称，便于 Horizon 监控和管理
        $this->onQueue('video-cover');
    }

    /**
     * 获取任务的唯一标识，防止重复任务
     *
     * @return string
     */
    public function uniqueId(): string
    {
        $videoIdentifier = $this->params['video_path'] ?? $this->params['video_url'] ?? '';
        $snapshotTime = $this->params['snapshot_time'] ?? 1000;

        return md5($videoIdentifier . '^' . $snapshotTime);
    }

    /**
     * Execute the job.
     *
     * @throws \Exception
     */
    public function handle()
    {
        Log::channel('job')->info('[视频封面生成队列开始]', [
            'params' => $this->maskParams(),
            'attempt' => $this->attempts()
        ]);

        try {
            // 0. 检查参数
            $this->validateParams();

            // 1. 构建视频截图 URL
            $snapshotUrl = $this->buildSnapshotUrl();

            Log::channel('job')->info('[视频封面生成] 截图URL构建完成', [
                'snapshot_url' => $snapshotUrl,
            ]);

            // 2. 获取截图图片流
            $imageContent = $this->fetchSnapshot($snapshotUrl);

            Log::channel('job')->info('[视频封面生成] 截图获取成功', [
                'content_length' => strlen($imageContent),
            ]);

            // 3. 上传封面到 TOS
            $coverPath = $this->uploadCoverToTos($imageContent);

            // 4. 生成封面 URL
            $fileUploadService = new FileUploadService();
            $coverUrl = $fileUploadService->generateFileUrl($coverPath, 'volcengine');

            Log::channel('job')->info('[视频封面生成队列完成]', [
                'params' => $this->maskParams(),
                'attempt' => $this->attempts(),
                'cover_path' => $coverPath,
                'cover_url' => $coverUrl,
            ]);

            // 5. 执行回调（如果有）
            if (!empty($this->params['callback']) && is_callable($this->params['callback'])) {
                call_user_func($this->params['callback'], [
                    'cover_path' => $coverPath,
                    'cover_url' => $coverUrl,
                ]);
            }

            return [
                'cover_path' => $coverPath,
                'cover_url' => $coverUrl,
            ];

        } catch (\Exception $e) {
            $msg = '[视频封面生成队列失败]: ' . $e->getMessage();

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
        Log::error('[视频封面生成最终失败]', [
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
            'video-cover',
            'video:' . ($this->params['video_path'] ?? 'url'),
            'time:' . ($this->params['snapshot_time'] ?? 1000),
        ];
    }

    /**
     * 验证参数
     *
     * @throws \Exception
     */
    protected function validateParams()
    {
        if (empty($this->params['video_path']) && empty($this->params['video_url'])) {
            throw new \Exception('缺少视频路径或URL参数');
        }
    }

    /**
     * 构建视频截图 URL
     *
     * @return string
     */
    protected function buildSnapshotUrl(): string
    {
        // 获取视频完整 URL
        if (!empty($this->params['video_url'])) {
            $videoUrl = $this->params['video_url'];
        } else {
            $fileUploadService = new FileUploadService();
            $videoUrl = $fileUploadService->generateFileUrl($this->params['video_path'], 'volcengine');
        }

        // 截图参数
        $snapshotTime = $this->params['snapshot_time'] ?? 1000; // 默认 1 秒
        $format = $this->params['format'] ?? 'jpg';
        $mode = $this->params['mode'] ?? 'fast';

        // 构建 x-tos-process 参数
        // 格式: video/snapshot,t_{时间毫秒},f_{格式},m_{模式}
        $processParam = sprintf(
            'video/snapshot,t_%d,f_%s,m_%s',
            $snapshotTime,
            $format,
            $mode
        );

        // 拼接 URL
        $separator = strpos($videoUrl, '?') !== false ? '&' : '?';

        return $videoUrl . $separator . 'x-tos-process=' . $processParam;
    }

    /**
     * 获取截图图片流
     *
     * @param string $snapshotUrl
     * @return string
     * @throws \Exception
     */
    protected function fetchSnapshot(string $snapshotUrl): string
    {
        $response = Http::timeout(60)->get($snapshotUrl);

        if (!$response->successful()) {
            throw new \Exception(sprintf(
                '获取视频截图失败: HTTP %d, %s',
                $response->status(),
                $response->body()
            ));
        }

        $content = $response->body();

        if (empty($content)) {
            throw new \Exception('获取视频截图失败: 返回内容为空');
        }

        // 验证是否为有效图片
        $imageInfo = @getimagesizefromstring($content);
        if ($imageInfo === false) {
            throw new \Exception('获取视频截图失败: 返回内容不是有效图片');
        }

        return $content;
    }

    /**
     * 上传封面到 TOS
     *
     * @param string $imageContent
     * @return string 存储路径
     * @throws \Exception
     */
    protected function uploadCoverToTos(string $imageContent): string
    {
        // 生成存储路径
        $coverPath = $this->generateCoverPath();

        // 上传到 TOS
        $disk = Storage::disk('volcengine');
        $uploaded = $disk->put($coverPath, $imageContent);

        if (!$uploaded) {
            throw new \Exception('封面上传到 TOS 失败');
        }

        return $coverPath;
    }

    /**
     * 生成封面存储路径
     *
     * @return string
     */
    protected function generateCoverPath(): string
    {
        // 如果指定了封面路径，直接使用
        if (!empty($this->params['cover_path'])) {
            return $this->params['cover_path'];
        }

        // 自动生成路径
        $now = Carbon::now();
        $year = $now->format('Y');
        $month = $now->format('m');
        $day = $now->format('d');

        $format = $this->params['format'] ?? 'jpg';
        $uuid = Str::uuid()->toString();

        return "covers/{$year}/{$month}/{$day}/{$uuid}.{$format}";
    }

    /**
     * 脱敏参数用于日志记录
     *
     * @return array
     */
    protected function maskParams(): array
    {
        return [
            'video_path' => $this->params['video_path'] ?? null,
            'video_url' => !empty($this->params['video_url']) ? '***' : null,
            'snapshot_time' => $this->params['snapshot_time'] ?? 1000,
            'format' => $this->params['format'] ?? 'jpg',
            'mode' => $this->params['mode'] ?? 'fast',
        ];
    }
}
