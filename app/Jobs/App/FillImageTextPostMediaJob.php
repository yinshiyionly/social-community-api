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
 * 填充图文帖子媒体信息（width、height）
 */
class FillImageTextPostMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;
    public $backoff = 10;

    /**
     * @var int
     */
    protected $postId;

    public function __construct(int $postId)
    {
        $this->postId = $postId;
        $this->onQueue('post-media');
    }

    public function handle()
    {
        Log::channel('job')->info('开始填充图文帖子媒体信息', [
            'job' => self::class,
            'post_id' => $this->postId,
            'attempt' => $this->attempts(),
        ]);

        $post = AppPostBase::query()
            ->select(['post_id', 'post_type', 'media_data', 'cover'])
            ->where('post_id', $this->postId)
            ->where('post_type', AppPostBase::POST_TYPE_IMAGE_TEXT)
            ->first();

        if (!$post) {
            Log::channel('job')->warning('图文帖子不存在，跳过处理', [
                'job' => self::class,
                'post_id' => $this->postId,
            ]);
            return;
        }

        $mediaData = $post->media_data ?? [];
        if (empty($mediaData)) {
            Log::channel('job')->info('图文帖子无媒体文件，跳过处理', [
                'job' => self::class,
                'post_id' => $this->postId,
            ]);
            return;
        }

        $urls = array_column($mediaData, 'url');
        $fileRecords = $this->getFileRecordsByUrls($urls);
        $updated = false;

        foreach ($mediaData as $index => $item) {
            if (empty($item['url'])) {
                continue;
            }
            if (!empty($item['width']) && !empty($item['height'])) {
                continue;
            }

            $fileRecord = $fileRecords[$item['url']] ?? null;
            if ($fileRecord) {
                $mediaData[$index]['type'] = 'image';
                $mediaData[$index]['width'] = $fileRecord->width ?? 0;
                $mediaData[$index]['height'] = $fileRecord->height ?? 0;
                $updated = true;
            }
        }

        // 如果 cover 为空则使用 media_data 的第一个 item 作为 cover 的值
        if (empty($post->cover) && !empty($mediaData)) {
            $post->cover = $mediaData[0];
        }

        if ($updated) {
            $post->media_data = $mediaData;
            $post->save();
            Log::channel('job')->info('图文帖子媒体信息填充完成', [
                'job' => self::class,
                'post_id' => $this->postId,
            ]);
        } else {
            Log::channel('job')->info('图文帖子媒体信息无需更新', [
                'job' => self::class,
                'post_id' => $this->postId,
            ]);
        }
    }

    protected function getFileRecordsByUrls(array $urls): array
    {
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
            ->select(['file_path', 'width', 'height'])
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

    protected function extractPathFromUrl(string $url): ?string
    {
        $parsed = parse_url($url);
        if (empty($parsed['path'])) {
            return null;
        }
        return ltrim($parsed['path'], '/');
    }

    public function failed(Throwable $exception)
    {
        Log::channel('job')->error('图文帖子媒体信息填充最终失败', [
            'job' => self::class,
            'post_id' => $this->postId,
            'error' => $exception->getMessage(),
        ]);
    }

    public function tags(): array
    {
        return ['post-media', 'post:' . $this->postId];
    }
}
