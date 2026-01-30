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
 * 填充视频帖子媒体信息（width、height、duration）
 */
class FillVideoPostMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;
    public int $backoff = 10;

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
        Log::channel('job')->info('开始填充视频帖子媒体信息', [
            'job' => self::class,
            'post_id' => $this->postId,
            'attempt' => $this->attempts(),
        ]);

        // 获取帖子数据
        $post = AppPostBase::query()
            ->select(['post_id', 'post_type', 'media_data', 'cover'])
            ->where('post_id', $this->postId)
            ->where('post_type', AppPostBase::POST_TYPE_VIDEO)
            ->first();

        // 帖子不存在
        if (!$post) {
            Log::channel('job')->warning('视频帖子不存在，跳过处理', [
                'job' => self::class,
                'post_id' => $this->postId,
            ]);
            return;
        }

        $mediaData = $post->media_data ?? [];
        $cover = $post->cover ?? [];
        $updated = false;

        // 收集所有 URL
        $urls = array_column($mediaData, 'url');
        if (!empty($cover['url'])) {
            $urls[] = $cover['url'];
        }

        if (empty($urls)) {
            Log::channel('job')->info('视频帖子无媒体文件，跳过处理', [
                'job' => self::class,
                'post_id' => $this->postId,
            ]);
            return;
        }

        // TODO 只处理当前项目存储桶的 URLs
        $fileRecords = $this->getFileRecordsByUrls($urls);

        // 填充视频信息
        foreach ($mediaData as $index => $item) {
            if (empty($item['url'])) {
                continue;
            }
            if (!empty($item['width']) && !empty($item['height']) && !empty($item['duration'])) {
                continue;
            }

            $fileRecord = $fileRecords[$item['url']] ?? null;
            if ($fileRecord) {
                $mediaData[$index]['type'] = 'video';
                $mediaData[$index]['width'] = $fileRecord->width ?? 0;
                $mediaData[$index]['height'] = $fileRecord->height ?? 0;
                $mediaData[$index]['duration'] = $fileRecord->duration ?? 0;
                $mediaData[$index]['cover'] = $fileRecord->extra['cover'] ?? '';
                $updated = true;
            }
        }

        // 填充封面信息
        // 用户设置了视频帖子的封面并且缺少封面宽度/高度信息
        if (!empty($cover['url']) && (empty($cover['width']) || empty($cover['height']))) {
            $fileRecord = $fileRecords[$cover['url']] ?? null;
            if ($fileRecord) {
                $cover['width'] = $fileRecord->width ?? 0;
                $cover['height'] = $fileRecord->height ?? 0;
                $updated = true;
            }
        }

        if (empty($cover['url']) && !empty($mediaData[0]) && !empty($mediaData[0]['cover'])) {
            // 用户没有设置视频帖子的封面, 则使用该视频在 app_file_records 中存储的 extra 信息中的 cover 作为封面
            // 然后同步视频的宽高给封面
            $cover['url'] = $mediaData[0]['cover'];
            $cover['width'] = $mediaData[0]['width'] ?? 0;
            $cover['height'] = $mediaData[0]['height'] ?? 0;
            $updated = true;
        }

        if ($updated) {
            $post->media_data = $mediaData;
            $post->cover = $cover;
            $post->save();
            Log::channel('job')->info('视频帖子媒体信息填充完成', [
                'job' => self::class,
                'post_id' => $this->postId,
            ]);
        } else {
            Log::channel('job')->info('视频帖子媒体信息无需更新', [
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
            ->select(['file_path', 'width', 'height', 'duration', 'extra'])
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
        Log::channel('job')->error('视频帖子媒体信息填充最终失败', [
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
