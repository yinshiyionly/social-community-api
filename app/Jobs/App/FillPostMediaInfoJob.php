<?php

namespace App\Jobs\App;

use App\Models\App\AppFileRecord;
use App\Models\App\AppPostBase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * 填充帖子媒体信息（width、height、type、duration）
 */
class FillPostMediaInfoJob implements ShouldQueue
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
     * 重试延迟时间（秒）
     *
     * @var int
     */
    public $backoff = 10;

    /**
     * @var int
     */
    protected $postId;

    /**
     * Create a new job instance.
     *
     * @param int $postId
     */
    public function __construct(int $postId)
    {
        $this->postId = $postId;
        $this->onQueue('post-media');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::channel('job')->info('开始填充帖子媒体信息', [
            'job' => self::class,
            'post_id' => $this->postId,
            'attempt' => $this->attempts(),
        ]);

        $post = AppPostBase::query()
            ->select(['post_id', 'media_data', 'cover'])
            ->where('post_id', $this->postId)
            ->first();

        if (!$post) {
            Log::channel('job')->warning('帖子不存在，跳过处理', [
                'job' => self::class,
                'post_id' => $this->postId,
            ]);
            return;
        }

        $mediaData = $post->media_data ?? [];
        $cover = $post->cover ?? [];
        $updated = false;

        // 收集所有需要查询的 URL
        $urls = array_column($mediaData, 'url');
        if (!empty($cover['url'])) {
            $urls[] = $cover['url'];
        }

        if (empty($urls)) {
            Log::channel('job')->info('帖子无媒体文件，跳过处理', [
                'job' => self::class,
                'post_id' => $this->postId,
            ]);
            return;
        }

        // 批量查询文件记录
        $fileRecords = $this->getFileRecordsByUrls($urls);
        // 填充 media_data
        foreach ($mediaData as $index => $item) {
            if (empty($item['url'])) {
                continue;
            }

            // 已有完整信息则跳过
            if (!empty($item['width']) && !empty($item['height']) && !empty($item['type'])) {
                continue;
            }

            $fileRecord = $fileRecords[$item['url']] ?? null;
            if ($fileRecord) {
                $mediaData[$index] = $this->fillMediaItem($item, $fileRecord);
                $updated = true;
            }
        }

        // 填充 cover
        if (!empty($cover['url']) && (empty($cover['width']) || empty($cover['height']))) {
            $fileRecord = $fileRecords[$cover['url']] ?? null;
            if ($fileRecord) {
                $cover['width'] = $fileRecord->width ?? 0;
                $cover['height'] = $fileRecord->height ?? 0;
                $updated = true;
            }
        }

        // 更新帖子
        if ($updated) {
            $post->media_data = $mediaData;
            $post->cover = $cover;
            $post->save();

            Log::channel('job')->info('帖子媒体信息填充完成', [
                'job' => self::class,
                'post_id' => $this->postId,
            ]);
        } else {
            Log::channel('job')->info('帖子媒体信息无需更新', [
                'job' => self::class,
                'post_id' => $this->postId,
            ]);
        }
    }

    /**
     * 根据 URL 批量获取文件记录
     *
     * @param array $urls
     * @return array [url => AppFileRecord]
     */
    protected function getFileRecordsByUrls(array $urls): array
    {
        // 从 URL 中提取 file_path（去掉域名部分）
        $paths = [];
        foreach ($urls as $url) {
            $path = $this->extractPathFromUrl($url);
            if ($path) {
                $paths[$path] = $url;
            }
        }

        if (empty($paths)) {
            return [];
        }

        $records = AppFileRecord::query()
            ->select(['file_path', 'mime_type', 'width', 'height', 'duration'])
            ->whereIn('file_path', array_keys($paths))
            ->get();

        $result = [];
        foreach ($records as $record) {
            $url = $paths[$record->file_path] ?? null;
            if ($url) {
                $result[$url] = $record;
            }
        }

        return $result;
    }

    /**
     * 从 URL 中提取存储路径
     *
     * @param string $url
     * @return string|null
     */
    protected function extractPathFromUrl(string $url): ?string
    {
        // 解析 URL，获取 path 部分
        $parsed = parse_url($url);
        if (empty($parsed['path'])) {
            return null;
        }

        // 去掉开头的斜杠
        return ltrim($parsed['path'], '/');
    }

    /**
     * 填充单个媒体项信息
     *
     * @param array $item
     * @param AppFileRecord $fileRecord
     * @return array
     */
    protected function fillMediaItem(array $item, AppFileRecord $fileRecord): array
    {
        // 确定类型
        if (empty($item['type'])) {
            if ($fileRecord->isVideo()) {
                $item['type'] = 'video';
            } else {
                $item['type'] = 'image';
            }
        }

        // 填充宽高
        if (empty($item['width'])) {
            $item['width'] = $fileRecord->width ?? 0;
        }
        if (empty($item['height'])) {
            $item['height'] = $fileRecord->height ?? 0;
        }

        // 视频填充时长
        if ($item['type'] === 'video' && empty($item['duration'])) {
            $item['duration'] = $fileRecord->duration ?? 0;
        }

        return $item;
    }

    /**
     * 任务失败处理
     *
     * @param Throwable $exception
     * @return void
     */
    public function failed(Throwable $exception)
    {
        Log::channel('job')->error('帖子媒体信息填充最终失败', [
            'job' => self::class,
            'post_id' => $this->postId,
            'error' => $exception->getMessage(),
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
            'post-media',
            'post:' . $this->postId,
        ];
    }
}
