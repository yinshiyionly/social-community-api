<?php

namespace App\Console\Commands;

use App\Console\LogTrait;
use App\Models\App\AppVideoBaijiayun;
use App\Services\BaijiayunLiveService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Throwable;

/**
 * 同步百家云点播视频到本地表 app_video_baijiayun。
 *
 * 设计目标：
 * 1. 幂等：重复执行不会产生重复业务数据（按 video_id 合并）。
 * 2. 可恢复：如果本地是软删记录，自动恢复并更新。
 * 3. 可观测：输出拉取与入库统计，便于巡检任务执行结果。
 */
class GetBaiJiaYunVideoList extends Command
{
    use LogTrait;

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
     * 执行命令入口。
     *
     * 步骤：
     * 1. 调用百家云接口全量分页拉取；
     * 2. 对缺失 file_md5 的记录按需补拉视频详情；
     * 3. 将拉取结果按 video_id 同步到本地；
     * 4. 输出同步统计（新增/更新/恢复/跳过/失败）。
     *
     * 约定：
     * - 业务异常不抛出中断命令，统一累计到失败统计；
     * - 退出码固定返回 0，依赖日志和统计判断执行质量。
     */
    public function handle()
    {
        $videoList = $this->getData();
        $this->infoLog('拉取完成，共获取视频 ' . count($videoList) . ' 条');

        // 拉取为空通常表示接口无数据或拉取过程中提前结束，直接返回。
        if (empty($videoList)) {
            return 0;
        }

        // 入库结果是聚合统计，后续可用于告警阈值判断。
        $result = $this->syncToDatabase($videoList);

        $this->infoLog(sprintf(
            '入库完成：新增 %d 条，更新 %d 条，恢复 %d 条，跳过 %d 条，失败 %d 条，md5补拉失败 %d 条',
            $result['created'],
            $result['updated'],
            $result['restored'],
            $result['skipped'],
            $result['failed'],
            $result['md5_fetch_failed']
        ));

        // 为避免控制台刷屏，仅展示前 5 条错误明细。
        if (!empty($result['errors'])) {
            $maxErrorCount = 5;
            foreach (array_slice($result['errors'], 0, $maxErrorCount) as $errorMessage) {
                $this->errorLog($errorMessage);
            }

            if (count($result['errors']) > $maxErrorCount) {
                $this->errorLog('其余错误已省略，请查看日志或重试');
            }
        }

        return 0;
    }

    /**
     * 分页拉取百家云点播视频列表（全量）。
     *
     * 返回结构示例（单条）：
     * [
     *   'video_id' => 312435382,
     *   'status' => 100,
     *   'total_size' => 194914036,
     *   'length' => 890,
     *   'publish_status' => 1,
     *   'name' => 'xxx',
     *   'create_time' => '2026-01-23 18:40:57',
     *   'preface_url' => 'http://...',
     *   'play_url' => 'https://...',
     * ]
     *
     * 终止条件（任一命中即结束）：
     * 1. 接口返回失败；
     * 2. 当前页为空；
     * 3. 已拉取数量达到 total；
     * 4. 当前页条数不足 pageSize（视为最后一页）。
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
            // 约定服务层已把 HTTP 失败和业务失败收敛为 success=false。
            $result = $service->videoGetVideoList($page, $pageSize);
            if (!($result['success'] ?? false)) {
                $this->errorLog(sprintf(
                    '第 %d 页拉取失败，错误码：%s，错误信息：%s',
                    $page,
                    $result['error_code'] ?? 'UNKNOWN',
                    $result['error_message'] ?? 'UNKNOWN'
                ));
                break;
            }

            $list = $result['data']['list'] ?? [];
            // total 用于“已拉取/总量”展示与终止判断，接口缺失时沿用上次值。
            $total = (int)($result['data']['total'] ?? $total);

            // 返回空页时提前结束，防止无效轮询。
            if (empty($list)) {
                $this->warnLog("第 {$page} 页返回空数据，提前结束");
                break;
            }

            $allVideos = array_merge($allVideos, $list);
            $this->infoLog("第 {$page} 页拉取成功，本页 " . count($list) . " 条，累计 " . count($allVideos) . "/{$total}");

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
     * 2. video_id 已存在：按最新字段更新（含 create_time -> created_at 对齐）
     * 3. 仅存在软删记录：先恢复再更新
     * 4. file_md5 缺失时额外调用 videoGetInfo 补拉，不因单条补拉失败中断任务
     *
     * 结果结构说明：
     * - created：新增记录数
     * - updated：已有记录字段变更（含 created_at 纠偏）后更新的数量
     * - restored：软删恢复数量
     * - skipped：被跳过数量（无效 video_id 或无变化记录）
     * - failed：处理失败数量
     * - md5_fetch_failed：file_md5 补拉失败数量
     * - errors：失败/跳过明细消息
     *
     * @param array<int, array<string, mixed>> $videoList
     * @return array{
     *   created:int,
     *   updated:int,
     *   restored:int,
     *   skipped:int,
     *   failed:int,
     *   md5_fetch_failed:int,
     *   errors:array<int, string>
     * }
     */
    protected function syncToDatabase(array $videoList): array
    {
        $result = [
            'created'          => 0,
            'updated'          => 0,
            'restored'         => 0,
            'skipped'          => 0,
            'failed'           => 0,
            'md5_fetch_failed' => 0,
            'errors'           => [],
        ];
        $service = new BaijiayunLiveService();

        foreach ($videoList as $video) {
            $videoId = (int)($video['video_id'] ?? 0);
            // video_id 是幂等键，缺失或非法时直接跳过。
            if ($videoId <= 0) {
                $result['skipped']++;
                $result['errors'][] = '跳过无效 video_id 数据';
                continue;
            }

            try {
                // 优先查未删除记录：正常场景应只有这一条。
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

                // 仅在“列表无 md5 + 本地无 md5”时补拉详情，避免全量 getInfo 带来额外耗时。
                $fileMd5 = $this->resolveFileMd5($videoId, $video, $record, $service, $result);
                $payload = $this->buildVideoPayload($video, $fileMd5);
                $createdAt = $this->parseCreateTime($video);

                if ($record) {
                    $isRestored = false;
                    // 如果命中软删记录，先恢复后再更新字段。
                    if ($record->trashed()) {
                        $record->restore();
                        $result['restored']++;
                        $isRestored = true;
                    }

                    if ($createdAt !== null) {
                        $currentCreatedAt = $record->created_at
                            ? $record->created_at->format('Y-m-d H:i:s')
                            : null;
                        // 历史数据可能被写成同步时间，这里统一纠偏为百家云来源时间。
                        if ($currentCreatedAt !== $createdAt) {
                            $record->created_at = $createdAt;
                        }
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

                $newRecord = new AppVideoBaijiayun();
                $newRecord->fill(array_merge(['video_id' => $videoId], $payload));
                // 新增时尽量保留百家云原始创建时间，便于后续按来源时间排查。
                if ($createdAt !== null) {
                    // 通过属性赋值写入时间戳，避免被 $fillable 过滤导致回退为执行时间。
                    $newRecord->created_at = $createdAt;
                    $newRecord->updated_at = $createdAt;
                }

                $newRecord->save();
                $result['created']++;
            } catch (Throwable $e) {
                // 单条失败不影响后续记录处理，保证任务尽量完成更多数据同步。
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
     * 字段映射关系：
     * - video.name            -> app_video_baijiayun.name
     * - video.status          -> app_video_baijiayun.status
     * - video.total_size      -> app_video_baijiayun.total_size（库里是字符串）
     * - video.preface_url     -> app_video_baijiayun.preface_url
     * - video.play_url        -> app_video_baijiayun.play_url
     * - video.length          -> app_video_baijiayun.length
     * - video.width           -> app_video_baijiayun.width
     * - video.height          -> app_video_baijiayun.height
     * - video.file_md5        -> app_video_baijiayun.file_md5（缺失时会尝试补拉详情）
     * - video.publish_status  -> app_video_baijiayun.publish_status
     *
     * 兜底策略：
     * - 缺失字段按表默认语义给默认值，避免入库时触发 NOT NULL/类型问题。
     *
     * @param array<string, mixed> $video
     * @param string|null $fileMd5
     * @return array<string, mixed>
     */
    protected function buildVideoPayload(array $video, ?string $fileMd5): array
    {
        // 从百家云查询接口获取的视频库数据中没有 width 和 height
        $data = [
            'name'           => isset($video['name']) ? (string)$video['name'] : '',
            'status'         => isset($video['status']) ? (int)$video['status'] : AppVideoBaijiayun::STATUS_UPLOADING,
            'total_size'     => isset($video['total_size']) ? (string)$video['total_size'] : '0',
            'preface_url'    => isset($video['preface_url']) && $video['preface_url'] !== ''
                ? (string)$video['preface_url']
                : null,
            'play_url'       => isset($video['play_url']) && $video['play_url'] !== ''
                ? (string)$video['play_url']
                : null,
            'length'         => isset($video['length']) ? (int)$video['length'] : 0,
            'file_md5'       => $fileMd5,
            'publish_status' => isset($video['publish_status'])
                ? (int)$video['publish_status']
                : AppVideoBaijiayun::PUBLISH_STATUS_UNPUBLISHED,
        ];

        // 使用 getimagesize 从 preface_url 获取视频的宽高数据
        if (!empty($data['preface_url'])) {
            try {
                $imageSize = getimagesize($data['preface_url']);
                $data['width'] = $imageSize[0];
                $data['height'] = $imageSize[1];
                $msg = sprintf(
                    "使用 getimagesize 获取封面宽高成功, name: %s, width: %s, height: %s",
                    $data['name'],
                    $data['width'],
                    $data['height']
                );
                $this->infoLog($msg);
            } catch (\Exception $e) {
                $data['width'] = 0;
                $data['height'] = 0;
                $msg = sprintf(
                    "使用 getimagesize 获取封面宽高失败, name: %s, 错误原因: %s",
                    $data['name'],
                    $e->getMessage()
                );
                $this->errorLog($msg);
            }
        } else {
            $data['width'] = 0;
            $data['height'] = 0;
        }

        return $data;


        return [
            'name'           => isset($video['name']) ? (string)$video['name'] : '',
            'status'         => isset($video['status']) ? (int)$video['status'] : AppVideoBaijiayun::STATUS_UPLOADING,
            'total_size'     => isset($video['total_size']) ? (string)$video['total_size'] : '0',
            'preface_url'    => isset($video['preface_url']) && $video['preface_url'] !== ''
                ? (string)$video['preface_url']
                : null,
            'play_url'       => isset($video['play_url']) && $video['play_url'] !== ''
                ? (string)$video['play_url']
                : null,
            'length'         => isset($video['length']) ? (int)$video['length'] : 0,
            'width'          => isset($video['width']) ? (int)$video['width'] : 0,
            'height'         => isset($video['height']) ? (int)$video['height'] : 0,
            'file_md5'       => $fileMd5,
            'publish_status' => isset($video['publish_status'])
                ? (int)$video['publish_status']
                : AppVideoBaijiayun::PUBLISH_STATUS_UNPUBLISHED,
        ];
    }

    /**
     * 解析当前同步应写入的 file_md5。
     *
     * 规则：
     * 1. 列表返回有 file_md5 时直接使用；
     * 2. 列表缺失但本地已有时沿用本地值，避免被空值覆盖；
     * 3. 仅在两者都缺失时调用 videoGetInfo 补拉。
     *
     * @param int $videoId
     * @param array<string, mixed> $video
     * @param AppVideoBaijiayun|null $record
     * @param BaijiayunLiveService $service
     * @param array<string, mixed> $result
     * @return string|null
     */
    protected function resolveFileMd5(
        int                  $videoId,
        array                $video,
        ?AppVideoBaijiayun   $record,
        BaijiayunLiveService $service,
        array                &$result
    ): ?string
    {
        $listFileMd5 = $this->normalizeFileMd5($video['file_md5'] ?? null);
        if ($listFileMd5 !== null) {
            return $listFileMd5;
        }

        $recordFileMd5 = $record ? $this->normalizeFileMd5($record->file_md5) : null;
        if ($recordFileMd5 !== null) {
            return $recordFileMd5;
        }

        return $this->fetchFileMd5ByVideoId($videoId, $service, $result);
    }

    /**
     * 调用百家云 videoGetInfo 补拉 file_md5。
     *
     * 失败策略：
     * - 单条补拉失败仅记录错误并继续主流程；
     * - 返回 null 表示本次未获取到有效 md5。
     *
     * @param int $videoId
     * @param BaijiayunLiveService $service
     * @param array<string, mixed> $result
     * @return string|null
     */
    protected function fetchFileMd5ByVideoId(int $videoId, BaijiayunLiveService $service, array &$result): ?string
    {
        $infoResult = $service->videoGetInfo($videoId);
        if (!($infoResult['success'] ?? false)) {
            $result['md5_fetch_failed']++;
            $result['errors'][] = sprintf(
                'video_id=%d 补拉 file_md5 失败：%s(%s)',
                $videoId,
                $infoResult['error_message'] ?? 'UNKNOWN',
                $infoResult['error_code'] ?? 'UNKNOWN'
            );
            return null;
        }

        $data = is_array($infoResult['data'] ?? null) ? $infoResult['data'] : [];
        $videoData = is_array($data['video'] ?? null) ? $data['video'] : [];
        $videoInfoData = is_array($data['video_info'] ?? null) ? $data['video_info'] : [];
        $fileMd5 = $this->normalizeFileMd5(
            $data['file_md5']
            ?? $videoData['file_md5']
               ?? $videoInfoData['file_md5']
                  ?? null
        );

        if ($fileMd5 === null) {
            $result['md5_fetch_failed']++;
            $result['errors'][] = sprintf('video_id=%d 补拉详情成功但缺少 file_md5 字段', $videoId);
            return null;
        }

        return $fileMd5;
    }

    /**
     * 归一化 file_md5 值。
     *
     * @param mixed $fileMd5
     * @return string|null
     */
    protected function normalizeFileMd5($fileMd5): ?string
    {
        if (!is_string($fileMd5)) {
            return null;
        }

        $fileMd5 = trim($fileMd5);
        return $fileMd5 === '' ? null : $fileMd5;
    }

    /**
     * 解析百家云 create_time 到数据库 datetime 字符串。
     *
     * 输入通常是 "Y-m-d H:i:s"，例如 "2026-01-23 18:40:57"。
     * 若为空或解析失败，返回 null，让 Eloquent 使用当前执行时间。
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
            // 时间格式异常时不阻断主流程，回退为 Eloquent 自动时间戳。
            return null;
        }
    }
}
