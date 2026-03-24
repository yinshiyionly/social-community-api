<?php

namespace App\Console\Commands;

use App\Console\LogTrait;
use App\Models\App\AppLivePlayback;
use App\Models\App\AppLiveRoom;
use App\Services\BaijiayunLiveService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Throwable;

/**
 * 同步百家云回放列表到本地表 app_live_playback。
 *
 * 职责：
 * 1. 分页拉取百家云回放列表并做幂等同步；
 * 2. 通过 third_party_room_id 关联本地直播间 room_id；
 * 3. 仅在本地缺失 player_token 时补拉回放 token；
 * 4. 输出新增/更新/恢复/跳过/失败统计，便于任务巡检。
 *
 * 触发时机：
 * - 支持手动执行或由调度任务定时执行，适用于回放库增量巡检同步。
 */
class GetBaiJiaYunPlaybackList extends Command
{
    use LogTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'baijiayun:sync-playback-list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '同步百家云回放列表到本地库';

    /**
     * 本地直播间ID缓存（key=third_party_room_id, value=room_id|null）。
     *
     * @var array<string, int|null>
     */
    protected $roomIdCache = [];

    /**
     * 回放 token 缓存（key=third_party_room_id, value=player_token）。
     *
     * 设计意图：
     * - 同一房间可能对应多条回放，单次任务内复用 token，避免重复调用第三方接口。
     *
     * @var array<string, string>
     */
    protected $playbackTokenCache = [];

    /**
     * 执行命令入口。
     *
     * 步骤：
     * 1. 分页调用百家云 playback/getList 拉取全量回放；
     * 2. 对本地缺失 player_token 的回放补拉 token；
     * 3. 按 playback_id 幂等同步到本地表；
     * 4. 输出聚合统计（新增/更新/恢复/跳过/失败/房间未匹配/token补拉）。
     *
     * 失败策略：
     * - 接口分页失败时提前结束拉取并输出错误；
     * - 回放 token 获取失败仅记录并继续；
     * - 单条入库失败不影响后续记录处理；
     * - 退出码固定返回 0，依赖统计与日志判断执行质量。
     *
     * @return int
     */
    public function handle()
    {
        // 命令实例可能被复用，执行前清空缓存，避免跨批次污染。
        $this->roomIdCache = [];
        $this->playbackTokenCache = [];

        $playbackList = $this->getData();
        $this->infoLog('拉取完成，共获取回放 ' . count($playbackList) . ' 条');

        // 拉取为空通常表示接口无数据或拉取过程中提前结束，直接返回。
        if (empty($playbackList)) {
            return 0;
        }

        $result = $this->syncToDatabase($playbackList);

        $this->infoLog(sprintf(
            '入库完成：新增 %d 条，更新 %d 条，恢复 %d 条，跳过 %d 条，失败 %d 条，房间未匹配 %d 条，token补拉成功 %d 条，token补拉失败 %d 条',
            $result['created'],
            $result['updated'],
            $result['restored'],
            $result['skipped'],
            $result['failed'],
            $result['room_unmatched'],
            $result['token_fetched'],
            $result['token_fetch_failed']
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
     * 分页拉取百家云回放列表（全量）。
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
        $pageSize = 10;
        $page = 1;
        $total = 0;
        $allPlaybacks = [];

        $service = $this->createBaijiayunLiveService();

        while (true) {
            // 约定服务层已把 HTTP 失败和业务失败收敛为 success=false。
            $result = $service->playbackGetList($page, $pageSize);
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
            $total = (int)($result['data']['total'] ?? $total);

            // 返回空页时提前结束，防止无效轮询。
            if (empty($list)) {
                $this->warnLog("第 {$page} 页返回空数据，提前结束");
                break;
            }

            $allPlaybacks = array_merge($allPlaybacks, $list);
            $this->infoLog("第 {$page} 页拉取成功，本页 " . count($list) . ' 条，累计 ' . count($allPlaybacks) . "/{$total}");

            // 终止条件：达到总数，或本页不足 pageSize（最后一页）。
            if (($total > 0 && count($allPlaybacks) >= $total) || count($list) < $pageSize) {
                break;
            }

            $page++;
        }

        return $allPlaybacks;
    }

    /**
     * 将百家云回放列表同步到 app_live_playback。
     *
     * 同步策略：
     * 1. playback_id 不存在：新增；
     * 2. playback_id 已存在：按最新字段更新；
     * 3. 仅存在软删记录：先恢复再更新；
     * 4. third_party_room_id 无法匹配本地 room_id 时保留数据并将 room_id 置为 NULL；
     * 5. player_token 仅在本地为空时补拉，避免重复调用第三方 token 接口。
     *
     * @param array<int, array<string, mixed>> $playbackList
     * @return array{
     *   created:int,
     *   updated:int,
     *   restored:int,
     *   skipped:int,
     *   failed:int,
     *   room_unmatched:int,
     *   token_fetched:int,
     *   token_fetch_failed:int,
     *   errors:array<int, string>
     * }
     */
    protected function syncToDatabase(array $playbackList): array
    {
        $result = [
            'created'            => 0,
            'updated'            => 0,
            'restored'           => 0,
            'skipped'            => 0,
            'failed'             => 0,
            'room_unmatched'     => 0,
            'token_fetched'      => 0,
            'token_fetch_failed' => 0,
            'errors'             => [],
        ];
        $service = $this->createBaijiayunLiveService();

        foreach ($playbackList as $playback) {
            $playbackId = (int)($playback['playback_id'] ?? 0);
            // playback_id 是幂等键，缺失或非法时直接跳过。
            if ($playbackId <= 0) {
                $result['skipped']++;
                $result['errors'][] = '跳过无效 playback_id 数据';
                continue;
            }

            try {
                $record = AppLivePlayback::query()
                    ->where('playback_id', $playbackId)
                    ->orderByDesc('id')
                    ->first();

                // 仅在未删除记录不存在时，才回退查软删记录，避免误恢复历史脏数据。
                if (!$record) {
                    $record = AppLivePlayback::withTrashed()
                        ->where('playback_id', $playbackId)
                        ->orderByDesc('id')
                        ->first();
                }

                $thirdPartyRoomId = $this->normalizeThirdPartyRoomId($playback['room_id'] ?? null);
                $localRoomId = $this->resolveLocalRoomId($thirdPartyRoomId, $result);
                $playerToken = $this->resolvePlaybackToken(
                    $playback,
                    $thirdPartyRoomId,
                    $record ? (string)$record->player_token : '',
                    $service,
                    $result
                );
                $payload = $this->buildPlaybackPayload($playback, $thirdPartyRoomId, $localRoomId, $playerToken);

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

                $newRecord = new AppLivePlayback();
                $newRecord->fill(array_merge(['playback_id' => $playbackId], $payload));
                $newRecord->save();

                $result['created']++;
            } catch (Throwable $e) {
                // 单条失败不影响后续记录处理，保证任务尽量完成更多数据同步。
                $result['failed']++;
                $result['errors'][] = sprintf(
                    'playback_id=%s 入库失败：%s',
                    $playback['playback_id'] ?? 'UNKNOWN',
                    $e->getMessage()
                );
            }
        }

        return $result;
    }

    /**
     * 将百家云回放字段映射为本地表字段。
     *
     * @param array<string, mixed> $playback
     * @param string $thirdPartyRoomId
     * @param int|null $localRoomId
     * @param string $playerToken
     * @return array<string, mixed>
     */
    protected function buildPlaybackPayload(
        array  $playback,
        string $thirdPartyRoomId,
        ?int   $localRoomId,
        string $playerToken
    ): array
    {
        return [
            'room_id'              => $localRoomId,
            'third_party_room_id'  => $thirdPartyRoomId,
            'session_id'           => isset($playback['session_id']) ? (int)$playback['session_id'] : 0,
            'video_id'             => isset($playback['video_id']) ? (int)$playback['video_id'] : 0,
            'name'                 => isset($playback['name']) ? (string)$playback['name'] : '',
            'status'               => isset($playback['status']) ? (int)$playback['status'] : AppLivePlayback::STATUS_GENERATING,
            'create_time'          => $this->parseCreateTime($playback),
            'length'               => isset($playback['length']) ? (int)$playback['length'] : 0,
            'total_transcode_size' => isset($playback['total_transcode_size']) ? (int)$playback['total_transcode_size'] : 0,
            'play_times'           => isset($playback['play_times']) ? (int)$playback['play_times'] : 0,
            'play_url'             => isset($playback['play_url']) ? (string)$playback['play_url'] : '',
            'preface_url'          => isset($playback['preface_url']) && $playback['preface_url'] !== ''
                ? (string)$playback['preface_url']
                : null,
            'player_token'         => $playerToken,
            'publish_status'       => isset($playback['publish_status'])
                ? (int)$playback['publish_status']
                : AppLivePlayback::PUBLISH_STATUS_UNSHIELDED,
            'version'              => isset($playback['version']) ? (int)$playback['version'] : 0,
        ];
    }

    /**
     * 解析回放 player_token。
     *
     * 规则：
     * 1. 本地已有非空 token 时直接复用，不重复请求；
     * 2. 本地 token 为空时，按 third_party_room_id 调用 getPlayerToken；
     * 3. 调用失败仅记录错误并返回空串，不中断主同步流程。
     *
     * @param array<string, mixed> $playback
     * @param string $thirdPartyRoomId
     * @param string $existingToken
     * @param BaijiayunLiveService $service
     * @param array<string, mixed> $result
     * @return string
     */
    protected function resolvePlaybackToken(
        array                $playback,
        string               $thirdPartyRoomId,
        string               $existingToken,
        BaijiayunLiveService $service,
        array                &$result
    ): string
    {
        $normalizedToken = $this->normalizePlayerToken($existingToken);
        if ($normalizedToken !== '') {
            return $normalizedToken;
        }

        if ($thirdPartyRoomId === '') {
            $result['token_fetch_failed']++;
            $result['errors'][] = sprintf(
                'playback_id=%s 获取回放token失败：room_id 缺失或非法',
                $playback['playback_id'] ?? 'UNKNOWN'
            );
            return '';
        }

        if (array_key_exists($thirdPartyRoomId, $this->playbackTokenCache)) {
            return $this->playbackTokenCache[$thirdPartyRoomId];
        }

        $roomId = $this->parseThirdPartyRoomId($thirdPartyRoomId);
        if ($roomId === null) {
            $result['token_fetch_failed']++;
            $result['errors'][] = sprintf(
                'playback_id=%s 获取回放token失败：room_id=%s 不是有效正整数',
                $playback['playback_id'] ?? 'UNKNOWN',
                $thirdPartyRoomId
            );
            return '';
        }

        $tokenResult = $service->playbackGetPlayerToken($roomId);
        $token = $this->normalizePlayerToken($tokenResult['data']['token'] ?? '');

        if (($tokenResult['success'] ?? false) && $token !== '') {
            $this->playbackTokenCache[$thirdPartyRoomId] = $token;
            $result['token_fetched']++;
            return $token;
        }

        // 缓存空串，避免同一 room 在单次任务内重复触发失败请求。
        $this->playbackTokenCache[$thirdPartyRoomId] = '';
        $result['token_fetch_failed']++;
        $result['errors'][] = sprintf(
            'playback_id=%s 获取回放token失败：%s(%s)',
            $playback['playback_id'] ?? 'UNKNOWN',
            $tokenResult['error_message'] ?? 'TOKEN_EMPTY',
            $tokenResult['error_code'] ?? 'TOKEN_EMPTY'
        );

        return '';
    }

    /**
     * 解析百家云教室号（room_id）并统一为字符串。
     *
     * @param mixed $roomId
     * @return string
     */
    protected function normalizeThirdPartyRoomId($roomId): string
    {
        if (!is_scalar($roomId)) {
            return '';
        }

        return trim((string)$roomId);
    }

    /**
     * 解析第三方 room_id 为正整数。
     *
     * @param string $thirdPartyRoomId
     * @return int|null
     */
    protected function parseThirdPartyRoomId(string $thirdPartyRoomId): ?int
    {
        if (!preg_match('/^\d+$/', $thirdPartyRoomId)) {
            return null;
        }

        $roomId = (int)$thirdPartyRoomId;
        return $roomId > 0 ? $roomId : null;
    }

    /**
     * 通过 third_party_room_id 匹配本地直播间 room_id。
     *
     * 匹配失败策略：
     * - 返回 null，不中断同步；
     * - 累计 room_unmatched 计数，供后续排查直播间映射数据。
     *
     * @param string $thirdPartyRoomId
     * @param array<string, mixed> $result
     * @return int|null
     */
    protected function resolveLocalRoomId(string $thirdPartyRoomId, array &$result): ?int
    {
        if ($thirdPartyRoomId === '') {
            $result['room_unmatched']++;
            return null;
        }

        if (!array_key_exists($thirdPartyRoomId, $this->roomIdCache)) {
            $room = AppLiveRoom::query()
                ->where('third_party_room_id', $thirdPartyRoomId)
                ->orderByDesc('room_id')
                ->first(['room_id']);

            $this->roomIdCache[$thirdPartyRoomId] = $room ? (int)$room->room_id : null;
        }

        if ($this->roomIdCache[$thirdPartyRoomId] === null) {
            $result['room_unmatched']++;
        }

        return $this->roomIdCache[$thirdPartyRoomId];
    }

    /**
     * 标准化 player_token。
     *
     * @param mixed $token
     * @return string
     */
    protected function normalizePlayerToken($token): string
    {
        if (!is_string($token)) {
            return '';
        }

        return trim($token);
    }

    /**
     * 解析百家云 create_time 到数据库 datetime 字符串。
     *
     * 由于 create_time 在表中为 NOT NULL，解析失败时回退为当前时间，
     * 避免单条脏数据导致整个同步任务失败。
     *
     * @param array<string, mixed> $playback
     * @return string
     */
    protected function parseCreateTime(array $playback): string
    {
        $createTime = $playback['create_time'] ?? null;
        if (!is_string($createTime) || trim($createTime) === '') {
            return Carbon::now()->toDateTimeString();
        }

        try {
            return Carbon::parse($createTime)->toDateTimeString();
        } catch (Throwable $e) {
            return Carbon::now()->toDateTimeString();
        }
    }

    /**
     * 创建百家云服务实例。
     *
     * 说明：
     * - 单独抽取工厂方法，便于测试阶段替换为桩服务。
     *
     * @return BaijiayunLiveService
     */
    protected function createBaijiayunLiveService(): BaijiayunLiveService
    {
        return new BaijiayunLiveService();
    }
}
