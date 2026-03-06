<?php

namespace App\Console\Commands;

use App\Models\App\AppVideoBaijiayun;
use App\Services\BaijiayunLiveService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Throwable;

class GetBaiJiaYunVideoList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'a:a';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '同步百家云点播视频列表到本地库';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $videoList = $this->getData();
        $this->info('拉取完成，共获取视频 ' . count($videoList) . ' 条');

        if (empty($videoList)) {
            return 0;
        }

        $result = $this->syncToDatabase($videoList);

        $this->info(sprintf(
            '入库完成：新增 %d 条，更新 %d 条，恢复 %d 条，跳过 %d 条，失败 %d 条',
            $result['created'],
            $result['updated'],
            $result['restored'],
            $result['skipped'],
            $result['failed']
        ));

        if (!empty($result['errors'])) {
            $maxErrorCount = 5;
            foreach (array_slice($result['errors'], 0, $maxErrorCount) as $errorMessage) {
                $this->error($errorMessage);
            }

            if (count($result['errors']) > $maxErrorCount) {
                $this->error('其余错误已省略，请查看日志或重试');
            }
        }

        return 0;
    }

    public function getData(): array
    {
        $pageSize = 10;
        $page = 1;
        $total = 0;
        $allVideos = [];

        $service = new BaijiayunLiveService();

        while (true) {
            $result = $service->videoGetVideoList($page, $pageSize);
            if (!($result['success'] ?? false)) {
                $this->error(sprintf(
                    '第 %d 页拉取失败，错误码：%s，错误信息：%s',
                    $page,
                    $result['error_code'] ?? 'UNKNOWN',
                    $result['error_message'] ?? 'UNKNOWN'
                ));
                break;
            }

            $list = $result['data']['list'] ?? [];
            $total = (int)($result['data']['total'] ?? $total);

            if (empty($list)) {
                $this->warn("第 {$page} 页返回空数据，提前结束");
                break;
            }

            $allVideos = array_merge($allVideos, $list);
            $this->info("第 {$page} 页拉取成功，本页 " . count($list) . " 条，累计 " . count($allVideos) . "/{$total}");

            // 终止条件：达到总数，或本页不足 pageSize（最后一页）
            if (($total > 0 && count($allVideos) >= $total) || count($list) < $pageSize) {
                break;
            }

            $page++;
        }

        return $allVideos;
    }

    protected function syncToDatabase(array $videoList): array
    {
        $result = [
            'created' => 0,
            'updated' => 0,
            'restored' => 0,
            'skipped' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($videoList as $video) {
            $videoId = (int) ($video['video_id'] ?? 0);
            if ($videoId <= 0) {
                $result['skipped']++;
                $result['errors'][] = '跳过无效 video_id 数据';
                continue;
            }

            try {
                $payload = $this->buildVideoPayload($video);

                $record = AppVideoBaijiayun::query()
                    ->where('video_id', $videoId)
                    ->orderByDesc('id')
                    ->first();

                if (!$record) {
                    $record = AppVideoBaijiayun::withTrashed()
                        ->where('video_id', $videoId)
                        ->orderByDesc('id')
                        ->first();
                }

                if ($record) {
                    $isRestored = false;
                    if ($record->trashed()) {
                        $record->restore();
                        $result['restored']++;
                        $isRestored = true;
                    }

                    $record->fill($payload);
                    if ($record->isDirty()) {
                        $record->save();
                        $result['updated']++;
                    } elseif (!$isRestored) {
                        $result['skipped']++;
                    }

                    continue;
                }

                $createPayload = array_merge(['video_id' => $videoId], $payload);
                $createdAt = $this->parseCreateTime($video);
                if ($createdAt !== null) {
                    $createPayload['created_at'] = $createdAt;
                    $createPayload['updated_at'] = $createdAt;
                }

                AppVideoBaijiayun::query()->create($createPayload);
                $result['created']++;
            } catch (Throwable $e) {
                $result['failed']++;
                $result['errors'][] = sprintf(
                    'video_id=%s 入库失败：%s',
                    $video['video_id'] ?? 'UNKNOWN',
                    $e->getMessage()
                );
            }
        }

        return $result;
    }

    protected function buildVideoPayload(array $video): array
    {
        return [
            'name' => isset($video['name']) ? (string) $video['name'] : '',
            'status' => isset($video['status']) ? (int) $video['status'] : AppVideoBaijiayun::STATUS_UPLOADING,
            'total_size' => isset($video['total_size']) ? (string) $video['total_size'] : '0',
            'preface_url' => isset($video['preface_url']) && $video['preface_url'] !== ''
                ? (string) $video['preface_url']
                : null,
            'play_url' => isset($video['play_url']) && $video['play_url'] !== ''
                ? (string) $video['play_url']
                : null,
            'length' => isset($video['length']) ? (int) $video['length'] : 0,
            'width' => isset($video['width']) ? (int) $video['width'] : 0,
            'height' => isset($video['height']) ? (int) $video['height'] : 0,
            'publish_status' => isset($video['publish_status'])
                ? (int) $video['publish_status']
                : AppVideoBaijiayun::PUBLISH_STATUS_UNPUBLISHED,
        ];
    }

    protected function parseCreateTime(array $video): ?string
    {
        $createTime = $video['create_time'] ?? null;
        if (!is_string($createTime) || trim($createTime) === '') {
            return null;
        }

        try {
            return Carbon::parse($createTime)->toDateTimeString();
        } catch (Throwable $e) {
            return null;
        }
    }
}
