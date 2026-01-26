<?php

namespace App\Console\Commands;

use App\Models\App\AppPostBase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 刷新帖子排序分
 */
class RefreshPostSortScore extends Command
{
    /**
     * @var string
     */
    protected $signature = 'post:refresh-sort-score 
                            {--chunk=500 : 每批处理数量}
                            {--all : 刷新所有帖子，否则只刷新近7天}';

    /**
     * @var string
     */
    protected $description = '刷新帖子排序分（物化排序）';

    /**
     * 权重配置
     */
    const WEIGHT_LIKE = 3;
    const WEIGHT_COMMENT = 5;
    const WEIGHT_SHARE = 2;
    const WEIGHT_COLLECTION = 2;
    const WEIGHT_VIEW = 0.1;

    /**
     * 时间衰减因子
     */
    const DECAY_FACTOR = 1.5;
    const DECAY_BASE_HOURS = 2;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $chunkSize = (int) $this->option('chunk');
        $refreshAll = $this->option('all');

        Log::channel('job')->info('开始刷新帖子排序分', [
            'job' => self::class,
            'chunk_size' => $chunkSize,
            'refresh_all' => $refreshAll,
        ]);

        $query = AppPostBase::query()
            ->approved()
            ->visible();

        if (!$refreshAll) {
            $query->where('created_at', '>=', now()->subDays(7));
        }

        $total = $query->count();
        $processed = 0;

        $this->info("开始处理，共 {$total} 条帖子");

        $query->chunkById($chunkSize, function ($posts) use (&$processed, $total) {
            foreach ($posts as $post) {
                $score = $this->calculateScore($post);
                
                DB::table('app_post_base')
                    ->where('post_id', $post->post_id)
                    ->update(['sort_score' => $score]);

                $processed++;
            }

            $this->info("已处理 {$processed}/{$total}");
        }, 'post_id');

        Log::channel('job')->info('帖子排序分刷新完成', [
            'job' => self::class,
            'total' => $total,
            'processed' => $processed,
        ]);

        $this->info("处理完成，共更新 {$processed} 条");

        return 0;
    }

    /**
     * 计算排序分
     *
     * 公式：(互动加权分) / (时间衰减因子)
     *
     * @param AppPostBase $post
     * @return float
     */
    protected function calculateScore(AppPostBase $post): float
    {
        // 互动加权分
        $interactionScore = $post->like_count * self::WEIGHT_LIKE
            + $post->comment_count * self::WEIGHT_COMMENT
            + $post->share_count * self::WEIGHT_SHARE
            + $post->collection_count * self::WEIGHT_COLLECTION
            + $post->view_count * self::WEIGHT_VIEW;

        // 时间衰减（小时）
        $hoursAge = max(0, now()->diffInHours($post->created_at));
        $decayDivisor = pow($hoursAge + self::DECAY_BASE_HOURS, self::DECAY_FACTOR);

        // 避免除零
        if ($decayDivisor <= 0) {
            $decayDivisor = 1;
        }

        return round($interactionScore / $decayDivisor, 6);
    }
}
