<?php

namespace App\Jobs\App;

use App\Models\App\AppPostBase;
use App\Models\App\AppPostCollection;
use App\Models\App\AppPostComment;
use App\Models\App\AppPostCommentLike;
use App\Models\App\AppPostLike;
use App\Models\App\AppPostStat;
use App\Models\App\AppTopicPostRelation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * 删除帖子后的关联数据清理任务。
 *
 * 触发时机：
 * - App 删除帖子接口在主帖软删后触发。
 *
 * 影响范围：
 * 1. 清理帖子点赞、收藏、评论、评论点赞、话题关联、帖子统计；
 * 2. 回收会员 favorite_count 与话题 post_count（均不允许出现负数）。
 *
 * 队列与重试：
 * - 队列：post-cleanup；
 * - 失败后按 tries/backoff 重试，保证最终一致性。
 */
class CleanupDeletedPostRelationsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 最大尝试次数。
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * 超时时间（秒）。
     *
     * @var int
     */
    public int $timeout = 120;

    /**
     * 重试退避（秒）。
     *
     * @var int
     */
    public int $backoff = 10;

    /**
     * 唯一锁过期时间（秒）。
     *
     * @var int
     */
    public int $uniqueFor = 600;

    /**
     * @var int
     */
    protected int $postId;

    /**
     * @var int
     */
    protected int $postType;

    /**
     * @var int
     */
    protected int $operatorMemberId;

    public function __construct(int $postId, int $postType, int $operatorMemberId = 0)
    {
        $this->postId = $postId;
        $this->postType = $postType;
        $this->operatorMemberId = $operatorMemberId;

        $this->onQueue('post-cleanup');
    }

    /**
     * 唯一键按帖子 ID 维度控制，避免同一帖子并发清理。
     *
     * @return string
     */
    public function uniqueId(): string
    {
        return (string)$this->postId;
    }

    /**
     * 执行关联清理任务。
     *
     * 失败策略：
     * - 抛出异常触发队列重试；
     * - 所有写操作放在事务中，避免部分清理造成统计不一致。
     *
     * @return void
     * @throws Throwable
     */
    public function handle(): void
    {
        Log::channel('job')->info('开始清理已删除帖子关联数据', [
            'job' => self::class,
            'post_id' => $this->postId,
            'post_type' => $this->postType,
            'operator_member_id' => $this->operatorMemberId,
            'attempt' => $this->attempts(),
        ]);

        $cleanupStat = [
            'post_like_deleted' => 0,
            'post_collection_deleted' => 0,
            'post_comment_deleted' => 0,
            'post_comment_like_deleted' => 0,
            'topic_relation_deleted' => 0,
            'post_stat_deleted' => 0,
        ];

        try {
            DB::transaction(function () use (&$cleanupStat): void {
                $now = now();

                $topicDecrementRows = AppTopicPostRelation::query()
                    ->select(['topic_id', DB::raw('COUNT(*) as total')])
                    ->where('post_id', $this->postId)
                    ->groupBy('topic_id')
                    ->get();

                $favoriteDecrementRows = AppPostCollection::query()
                    ->select(['member_id', DB::raw('COUNT(*) as total')])
                    ->where('post_id', $this->postId)
                    ->groupBy('member_id')
                    ->get();

                $commentIds = AppPostComment::query()
                    ->withTrashed()
                    ->where('post_id', $this->postId)
                    ->pluck('comment_id')
                    ->map(static function ($value): int {
                        return (int)$value;
                    })
                    ->toArray();

                $cleanupStat['post_like_deleted'] = AppPostLike::query()
                    ->where('post_id', $this->postId)
                    ->delete();

                $cleanupStat['post_collection_deleted'] = AppPostCollection::query()
                    ->where('post_id', $this->postId)
                    ->delete();

                foreach ($favoriteDecrementRows as $row) {
                    $memberId = (int)$row->member_id;
                    $count = (int)$row->total;
                    if ($memberId <= 0 || $count <= 0) {
                        continue;
                    }

                    // 收藏数回收使用 GREATEST，避免并发扣减导致负数。
                    DB::table('app_member_base')
                        ->where('member_id', $memberId)
                        ->update([
                            'favorite_count' => DB::raw(sprintf('GREATEST(favorite_count - %d, 0)', $count)),
                        ]);
                }

                if (!empty($commentIds)) {
                    $cleanupStat['post_comment_like_deleted'] = AppPostCommentLike::query()
                        ->whereIn('comment_id', $commentIds)
                        ->delete();

                    AppPostComment::query()
                        ->withTrashed()
                        ->whereIn('comment_id', $commentIds)
                        ->update([
                            'status' => AppPostComment::STATUS_DELETED,
                            'updated_at' => $now,
                        ]);

                    $cleanupStat['post_comment_deleted'] = AppPostComment::query()
                        ->withTrashed()
                        ->whereIn('comment_id', $commentIds)
                        ->whereNull('deleted_at')
                        ->update([
                            'deleted_at' => $now,
                            'updated_at' => $now,
                        ]);
                }

                $cleanupStat['topic_relation_deleted'] = AppTopicPostRelation::query()
                    ->where('post_id', $this->postId)
                    ->delete();

                foreach ($topicDecrementRows as $row) {
                    $topicId = (int)$row->topic_id;
                    $count = (int)$row->total;
                    if ($topicId <= 0 || $count <= 0) {
                        continue;
                    }

                    // 话题帖子数回收同样需要防止负数。
                    DB::table('app_topic_stat')
                        ->where('topic_id', $topicId)
                        ->update([
                            'post_count' => DB::raw(sprintf('GREATEST(post_count - %d, 0)', $count)),
                        ]);
                }

                $cleanupStat['post_stat_deleted'] = AppPostStat::query()
                    ->where('post_id', $this->postId)
                    ->delete();
            });

            $postExists = AppPostBase::query()
                ->withTrashed()
                ->where('post_id', $this->postId)
                ->exists();

            Log::channel('job')->info('已删除帖子关联数据清理完成', [
                'job' => self::class,
                'post_id' => $this->postId,
                'post_type' => $this->postType,
                'operator_member_id' => $this->operatorMemberId,
                'post_exists' => $postExists,
                'cleanup_stat' => $cleanupStat,
            ]);
        } catch (Throwable $e) {
            Log::channel('job')->error('清理已删除帖子关联数据失败', [
                'job' => self::class,
                'post_id' => $this->postId,
                'post_type' => $this->postType,
                'operator_member_id' => $this->operatorMemberId,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * 最终失败后的处理。
     *
     * 当前策略：
     * - 仅记录错误日志用于告警与人工补偿；
     * - 不再抛异常，避免死循环重试。
     *
     * @param Throwable $e
     * @return void
     */
    public function failed(Throwable $e): void
    {
        Log::channel('job')->error('清理已删除帖子关联数据任务最终失败', [
            'job' => self::class,
            'post_id' => $this->postId,
            'post_type' => $this->postType,
            'operator_member_id' => $this->operatorMemberId,
            'error' => $e->getMessage(),
        ]);
    }

    /**
     * 任务标签，用于 Horizon 监控。
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'post-cleanup',
            'post:' . $this->postId,
            'post-type:' . $this->postType,
        ];
    }
}
