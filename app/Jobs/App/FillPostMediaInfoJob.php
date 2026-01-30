<?php

namespace App\Jobs\App;

use App\Models\App\AppPostBase;
use Illuminate\Support\Facades\Log;

/**
 * 帖子媒体信息填充调度器
 *
 * 根据帖子类型分发到对应的 Job：
 * - 图文帖子 -> FillImageTextPostMediaJob (队列: post-media)
 * - 视频帖子 -> FillVideoPostMediaJob (队列: post-media)
 * - 文章帖子 -> FillArticlePostMediaJob (队列: post-media)
 */
class FillPostMediaInfoJob
{
    /**
     * 根据帖子类型分发到对应的 Job
     *
     * @param int $postId
     * @param int|null $postType 帖子类型，不传则自动查询
     * @return void
     */
    public static function dispatch(int $postId, int $postType): void
    {
        switch ($postType) {
            case AppPostBase::POST_TYPE_IMAGE_TEXT:
                FillImageTextPostMediaJob::dispatch($postId);
                break;

            case AppPostBase::POST_TYPE_VIDEO:
                FillVideoPostMediaJob::dispatch($postId);
                break;

            case AppPostBase::POST_TYPE_ARTICLE:
                // FillArticlePostMediaJob::dispatch($postId);
                break;

            default:
                Log::channel('job')->warning('未知帖子类型，无法分发媒体填充任务', [
                    'post_id' => $postId,
                    'post_type' => $postType,
                ]);
        }
    }
}
