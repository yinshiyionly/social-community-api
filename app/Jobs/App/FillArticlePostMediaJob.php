<?php

namespace App\Jobs\App;

use App\Models\App\AppFileRecord;
use App\Models\App\AppPostBase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * 填充文章帖子封面信息（width、height）
 * 只适用于帖子媒体数据是保存在自己的 TOS 上的数据
 *
 */
class FillArticlePostMediaJob implements ShouldQueue
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
        Log::channel('job')->info('开始填充文章帖子封面信息', [
            'job'     => self::class,
            'post_id' => $this->postId,
            'attempt' => $this->attempts(),
        ]);

        $post = AppPostBase::query()
            ->select(['post_id', 'post_type', 'cover'])
            ->where('post_id', $this->postId)
            ->where('post_type', AppPostBase::POST_TYPE_ARTICLE)
            ->first();

        if (!$post) {
            Log::channel('job')->warning('文章帖子不存在，跳过处理', [
                'job'     => self::class,
                'post_id' => $this->postId,
            ]);
            return;
        }

        $cover = $post->cover ?? [];
        if (empty($cover['url'])) {
            Log::channel('job')->info('文章帖子无封面，跳过处理', [
                'job'     => self::class,
                'post_id' => $this->postId,
            ]);
            return;
        }
        $cover = json_decode($post->getRawOriginal('cover'), true);

        if (!empty($cover['width']) && !empty($cover['height'])) {
            Log::channel('job')->info('文章帖子封面信息已完整，跳过处理', [
                'job'     => self::class,
                'post_id' => $this->postId,
            ]);
            return;
        }

        $fileRecord = $this->getFileRecordByUrl($cover['url']);
        if (empty($fileRecord)) {
            Log::channel('job')->warning('文章帖子封面文件记录不存在,使用getimagesize模式', [
                'job'       => self::class,
                'post_id'   => $this->postId,
                'cover_url' => $cover['url'],
            ]);

            try {
                $imageSize = @getimagesize($post->cover['url']);
                if ($imageSize) {
                    $coverWidth = $imageSize[0];
                    $coverHeight = $imageSize[1];
                }
            } catch (\Exception $e) {
                $coverWidth = 0;
                $coverHeight = 0;
            }
        } else {
            $coverWidth = $fileRecord->width ?? 0;
            $coverHeight = $fileRecord->height ?? 0;
        }
        $cover['width'] = $coverWidth;
        $cover['height'] = $coverHeight;
        $post->cover = $cover;
        $post->save();

        Log::channel('job')->info('文章帖子封面信息填充完成', [
            'job'     => self::class,
            'post_id' => $this->postId,
        ]);
    }

    /**
     * 根据 file_path 获取文件记录
     *
     * @param string $url
     * @return Builder|Model|object|null
     */
    protected function getFileRecordByUrl(string $url)
    {
        // 2026.3.16 $url 使用 getRawOriginal 获取到数据库保存的原始值,不再需要额外解析路径了
        /*$path = $this->extractPathFromUrl($url);
        if (!$path) {
            return null;
        }

        return AppFileRecord::query()
            ->select(['file_path', 'width', 'height'])
            ->where('file_path', $path)
            ->first();*/

        if (empty($url)) {
            return null;
        }

        return AppFileRecord::query()
            ->select(['file_path', 'width', 'height'])
            ->where('file_path', $url)
            ->first();
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
        Log::channel('job')->error('文章帖子封面信息填充最终失败', [
            'job'     => self::class,
            'post_id' => $this->postId,
            'error'   => $exception->getMessage(),
        ]);
    }

    public function tags(): array
    {
        return ['post-media', 'post:' . $this->postId];
    }
}
