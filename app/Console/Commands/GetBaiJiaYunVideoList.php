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
    protected $signature = 'baijiayun:sync-video-list';

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
     * 执行命令：先全量拉取百家云视频，再做本地幂等同步。
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

    /**
     * 分页拉取百家云点播视频列表（全量）。
     *
     * @return array<int, array<string, mixed>>
     */
    public function getData(): array
    {
        // 单页拉取条数，按接口分页连续拉取直到命中终止条件。
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

            // 返回空页时提前结束，防止无效轮询。
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

    /**
     * 将百家云返回的视频列表同步到 app_video_baijiayun。
     *
     * 同步策略：
     * 1. video_id 不存在：新增
     * 2. video_id 已存在：按最新字段更新
     * 3. 仅存在软删记录：先恢复再更新
     *
     * @param array<int, array<string, mixed>> $videoList
     * @return array<string, mixed>
     */
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
            // video_id 是幂等键，缺失或非法时直接跳过。
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

                // 仅在未删除记录不存在时，才回退查软删记录，避免误恢复历史脏数据。
                if (!$record) {
                    $record = AppVideoBaijiayun::withTrashed()
                        ->where('video_id', $videoId)
                        ->orderByDesc('id')
                        ->first();
                }

                if ($record) {
                    $isRestored = false;
                    // 如果命中软删记录，先恢复后再更新字段。
                    if ($record->trashed()) {
                        $record->restore();
                        $result['restored']++;
                        $isRestored = true;
                    }

                    $record->fill($payload);
                    // 仅字段有变化时写库，减少不必要 update。
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
                // 新增时尽量保留百家云原始创建时间，便于后续按来源时间排查。
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

    /**
     * 将百家云接口字段映射为本地表字段。
     *
     * @param array<string, mixed> $video
     * @return array<string, mixed>
     */
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

    /**
     * 解析百家云 create_time 到数据库 datetime 字符串。
     *
     * @param array<string, mixed> $video
     * @return string|null
     */
    protected function parseCreateTime(array $video): ?string
    {
        $createTime = $video['create_time'] ?? null;
        if (!is_string($createTime) || trim($createTime) === '') {
            return null;
        }

        try {
            return Carbon::parse($createTime)->toDateTimeString();
        } catch (Throwable $e) {
            // 时间格式异常时不阻断主流程，回退为数据库默认时间。
            return null;
        }
    }
}
