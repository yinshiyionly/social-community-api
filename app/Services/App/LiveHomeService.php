<?php

namespace App\Services\App;

use App\Http\Requests\App\LiveHomeRequest;
use App\Models\App\AppLivePlayback;
use App\Models\App\AppLiveRoom;
use App\Models\App\AppLiveRoomReserve;
use App\Services\AppFileUploadService;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * App 端直播首页服务。
 *
 * 核心职责：
 * 1. 按 tab 聚合预告/回放列表与分页数据；
 * 2. 构建 latest 卡片（直播中优先，其次最近即将开播）；
 * 3. 统一输出直播卡片字段，收敛状态与 URL 处理口径。
 */
class LiveHomeService
{
    /**
     * @var AppFileUploadService
     */
    protected $fileUploadService;

    public function __construct(AppFileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * 获取直播首页数据。
     *
     * 关键规则：
     * 1. latest 在“直播中 + 即将开播”中选 1 条，且直播中优先；
     * 2. upcoming 仅包含未开始直播；
     * 3. replay 从 app_live_playback 查询并按 room_id 去重。
     *
     * @param string $tab
     * @param int $page
     * @param int $pageSize
     * @param int $memberId
     * @return array{
     *   latest:array<string, mixed>|null,
     *   tab:string,
     *   list:array<int, array<string, mixed>>,
     *   total:int,
     *   page:int,
     *   pageSize:int,
     *   hasMore:bool
     * }
     */
    public function getHomeData(string $tab, int $page, int $pageSize, int $memberId): array
    {
        $latestRow = $this->getLatestLiveOrUpcomingRow($memberId);
        if ($tab === LiveHomeRequest::TAB_REPLAY) {
            $paginator = $this->buildReplayListQuery()->paginate($pageSize, ['*'], 'page', $page);
            $list = $this->formatReplayList($paginator->items());
        } else {
            $paginator = $this->buildUpcomingListQuery($memberId)->paginate($pageSize, ['*'], 'page', $page);
            $list = $this->formatUpcomingList($paginator->items());
        }

        return [
            'latest'   => $latestRow ? $this->formatLatestItem($latestRow) : null,
            'tab'      => $tab,
            'list'     => $list,
            'total'    => (int)$paginator->total(),
            'page'     => (int)$paginator->currentPage(),
            'pageSize' => (int)$paginator->perPage(),
            'hasMore'  => $paginator->currentPage() < $paginator->lastPage(),
        ];
    }

    /**
     * 获取 latest 卡片（直播中优先 + 距当前最近）。
     *
     * 约束：
     * - 候选状态仅包含：直播中、未开始（且开播时间 >= 当前时间）；
     * - 直播中优先于未开始；
     * - 同优先级下按“开播时间与当前时间差”升序（越接近当前越优先）；
     * - 再按 room_id 倒序兜底，确保结果稳定。
     * - 无数据时返回 null，不做回放兜底。
     *
     * @param int $memberId
     * @return object|null
     */
    protected function getLatestLiveOrUpcomingRow(int $memberId)
    {
        $now = now();
        $rows = $this->buildLatestBaseQuery($memberId)
            ->where(function ($query) use ($now) {
                $query->where('r.live_status', AppLiveRoom::LIVE_STATUS_LIVING)
                    ->orWhere(function ($upcomingQuery) use ($now) {
                        $upcomingQuery->where('r.live_status', AppLiveRoom::LIVE_STATUS_NOT_STARTED)
                            ->whereRaw('COALESCE(r.actual_start_time, r.scheduled_start_time) >= ?', [$now]);
                    });
            })
            ->get();

        if ($rows->isEmpty()) {
            return null;
        }

        $nowTimestamp = $now->timestamp;

        return $rows->sort(function ($left, $right) use ($nowTimestamp) {
            $leftPriority = (int)$left->live_status === AppLiveRoom::LIVE_STATUS_LIVING ? 0 : 1;
            $rightPriority = (int)$right->live_status === AppLiveRoom::LIVE_STATUS_LIVING ? 0 : 1;

            if ($leftPriority !== $rightPriority) {
                return $leftPriority <=> $rightPriority;
            }

            $leftDistance = $this->distanceToNowSeconds($left->start_time ?? null, $nowTimestamp);
            $rightDistance = $this->distanceToNowSeconds($right->start_time ?? null, $nowTimestamp);

            if ($leftDistance !== $rightDistance) {
                return $leftDistance <=> $rightDistance;
            }

            return (int)$right->room_id <=> (int)$left->room_id;
        })->first();
    }

    /**
     * 构建 upcoming 分页查询。
     *
     * @param int $memberId
     * @return Builder
     */
    protected function buildUpcomingListQuery(int $memberId): Builder
    {
        return $this->buildUpcomingBaseQuery($memberId)
            ->orderBy('start_time', 'asc')
            ->orderByDesc('r.room_id');
    }

    /**
     * upcoming 基础查询。
     *
     * 查询来源：
     * - app_live_room：直播基础信息；
     * - app_live_room_stat：预约人数；
     * - app_live_room_reserve：当前用户预约态（登录态时）。
     *
     * @param int $memberId
     * @return Builder
     */
    protected function buildUpcomingBaseQuery(int $memberId): Builder
    {
        $query = DB::table('app_live_room as r')
            ->leftJoin('app_live_room_stat as rs', 'rs.room_id', '=', 'r.room_id')
            ->whereNull('r.deleted_at')
            ->where('r.status', AppLiveRoom::STATUS_ENABLED)
            ->where('r.live_status', AppLiveRoom::LIVE_STATUS_NOT_STARTED);

        $selectFields = [
            'r.room_id',
            'r.room_title',
            'r.room_cover',
            'r.scheduled_start_time',
            'r.actual_start_time',
            DB::raw('COALESCE(rs.reserve_count, 0) as reserve_count'),
            DB::raw('COALESCE(r.actual_start_time, r.scheduled_start_time) as start_time'),
        ];

        if ($memberId > 0) {
            $query->leftJoin('app_live_room_reserve as rr', function ($join) use ($memberId) {
                $join->on('rr.room_id', '=', 'r.room_id')
                    ->where('rr.member_id', '=', $memberId)
                    ->where('rr.status', '=', AppLiveRoomReserve::STATUS_RESERVED);
            });
            $selectFields[] = DB::raw('CASE WHEN rr.reserve_id IS NULL THEN 0 ELSE 1 END as is_reserved');
        } else {
            $selectFields[] = DB::raw('0 as is_reserved');
        }

        return $query->select($selectFields);
    }

    /**
     * latest 基础查询。
     *
     * 与 upcoming 的差异：
     * - 不在基础查询阶段限制 live_status，交由上层组合条件；
     * - 额外返回 live_status 字段，用于 latest 状态映射。
     *
     * @param int $memberId
     * @return Builder
     */
    protected function buildLatestBaseQuery(int $memberId): Builder
    {
        $query = DB::table('app_live_room as r')
            ->leftJoin('app_live_room_stat as rs', 'rs.room_id', '=', 'r.room_id')
            ->whereNull('r.deleted_at')
            ->where('r.status', AppLiveRoom::STATUS_ENABLED);

        $selectFields = [
            'r.room_id',
            'r.room_title',
            'r.room_cover',
            'r.live_status',
            DB::raw('COALESCE(rs.reserve_count, 0) as reserve_count'),
            DB::raw('COALESCE(r.actual_start_time, r.scheduled_start_time) as start_time'),
        ];

        if ($memberId > 0) {
            $query->leftJoin('app_live_room_reserve as rr', function ($join) use ($memberId) {
                $join->on('rr.room_id', '=', 'r.room_id')
                    ->where('rr.member_id', '=', $memberId)
                    ->where('rr.status', '=', AppLiveRoomReserve::STATUS_RESERVED);
            });
            $selectFields[] = DB::raw('CASE WHEN rr.reserve_id IS NULL THEN 0 ELSE 1 END as is_reserved');
        } else {
            $selectFields[] = DB::raw('0 as is_reserved');
        }

        return $query->select($selectFields);
    }

    /**
     * 构建 replay 分页查询（按 room_id 去重后再分页）。
     *
     * 过滤规则：
     * - 仅“转码成功 + 未屏蔽 + play_url 非空 + room_id 有效”的回放；
     * - 仅展示启用且未删除的直播间；
     * - 同一 room_id 仅保留 create_time 最新的一条回放。
     *
     * @return Builder
     */
    protected function buildReplayListQuery(): Builder
    {
        $latestPlaybackByRoom = DB::table('app_live_playback as p')
            ->join('app_live_room as r', function ($join) {
                $join->on('r.third_party_room_id', '=', 'p.third_party_room_id')
                    ->whereNull('r.deleted_at')
                    ->where('r.status', '=', AppLiveRoom::STATUS_ENABLED);
            })
            ->whereNull('p.deleted_at')
            ->where('p.status', AppLivePlayback::STATUS_TRANSCODE_SUCCESS)
            ->where('p.publish_status', AppLivePlayback::PUBLISH_STATUS_UNSHIELDED)
            ->whereNotNull('p.play_url')
            ->where('p.play_url', '!=', '')
            //->whereNotNull('p.room_id')
            // ->where('p.room_id', '>', 0)
            // DISTINCT ON 依赖先按去重键排序，再按“最新回放”排序。
            ->selectRaw(
                'DISTINCT ON (p.room_id)
                p.room_id,
                p.third_party_room_id,
                p.id as playback_pk,
                p.play_url as replay_url,
                p.length as playback_length,
                p.play_times as watch_count,
                p.create_time as playback_create_time,
                p.preface_url as playback_cover,
                p.player_token,
                r.room_title,
                r.room_cover,
                r.scheduled_start_time,
                r.actual_start_time,
                r.live_duration'
            )
            ->orderBy('p.room_id')
            ->orderByDesc('p.create_time')
            ->orderByDesc('p.id');

        return DB::query()
            ->fromSub($latestPlaybackByRoom, 'rp')
            ->select([
                'rp.room_id',
                'rp.third_party_room_id',
                'rp.room_title',
                'rp.room_cover',
                'rp.scheduled_start_time',
                'rp.actual_start_time',
                'rp.replay_url',
                'rp.watch_count',
                'rp.playback_length',
                'rp.playback_create_time',
                'rp.playback_cover',
                'rp.player_token',
                'rp.live_duration',
                'rp.playback_pk',
            ])
            ->orderByDesc('rp.playback_create_time')
            ->orderByDesc('rp.playback_pk');
    }

    /**
     * 批量格式化 upcoming 列表。
     *
     * @param array<int, object> $rows
     * @return array<int, array<string, mixed>>
     */
    protected function formatUpcomingList(array $rows): array
    {
        return collect($rows)
            ->map(function ($row) {
                return $this->formatUpcomingItem($row);
            })
            ->values()
            ->all();
    }

    /**
     * 批量格式化 replay 列表。
     *
     * @param array<int, object> $rows
     * @return array<int, array<string, mixed>>
     */
    protected function formatReplayList(array $rows): array
    {
        return collect($rows)
            ->map(function ($row) {
                return $this->formatReplayItem($row);
            })
            ->values()
            ->all();
    }

    /**
     * 格式化 upcoming 卡片。
     *
     * @param object $row
     * @return array<string, mixed>
     */
    protected function formatUpcomingItem($row): array
    {
        $isReserved = (int)($row->is_reserved ?? 0) === 1;

        return [
            'id'           => (int)($row->room_id ?? 0),
            'title'        => (string)($row->room_title ?? ''),
            'cover'        => $this->buildCoverUrl((string)($row->room_cover ?? '')),
            'startTime'    => $this->formatDateTime($row->start_time ?? null),
            'status'       => LiveHomeRequest::TAB_UPCOMING,
            'reserveCount' => (int)($row->reserve_count ?? 0),
            'isReserved'   => $isReserved,
            'actionText'   => $isReserved ? '已预约' : '预约',
        ];
    }

    /**
     * 格式化 latest 卡片。
     *
     * latest 仅可能输出两种状态：
     * - live：按钮文案固定“直播中”；
     * - upcoming：按钮文案按预约态返回“预约/已预约”。
     *
     * @param object $row
     * @return array<string, mixed>
     */
    protected function formatLatestItem($row): array
    {
        $isReserved = (int)($row->is_reserved ?? 0) === 1;
        $isLiving = (int)($row->live_status ?? AppLiveRoom::LIVE_STATUS_NOT_STARTED) === AppLiveRoom::LIVE_STATUS_LIVING;

        return [
            'id' => (int)($row->room_id ?? 0),
            'title' => (string)($row->room_title ?? ''),
            'cover' => $this->buildCoverUrl((string)($row->room_cover ?? '')),
            'startTime' => $this->formatDateTime($row->start_time ?? null),
            'status' => $isLiving ? 'live' : LiveHomeRequest::TAB_UPCOMING,
            'reserveCount' => (int)($row->reserve_count ?? 0),
            'isReserved' => $isReserved,
            'actionText' => $isLiving ? '直播中' : ($isReserved ? '已预约' : '预约'),
        ];
    }

    /**
     * 格式化 replay 卡片。
     *
     * @param object $row
     * @return array<string, mixed>
     */
    protected function formatReplayItem($row): array
    {
        $startTime = $row->actual_start_time ?? $row->scheduled_start_time ?? $row->playback_create_time ?? null;

        return [
            'id'          => (int)($row->third_party_room_id ?? 0),
            'title'       => (string)($row->room_title ?? ''),
            'cover'       => $this->buildCoverUrl(
                (string)($row->room_cover ?? ''),
                (string)($row->playback_cover ?? '')
            ),
            'startTime'   => $this->formatDateTime($startTime),
            'status'      => LiveHomeRequest::TAB_REPLAY,
            'watchCount'  => (int)($row->watch_count ?? 0),
            'durationSec' => $this->buildReplayDuration($row),
            'replayUrl'   => $this->normalizeUrl((string)($row->replay_url ?? '')),
            'actionText'  => '回放',
            'player_token' => (string)($row->player_token ?? ''),
        ];
    }

    /**
     * 构建回放时长（秒）。
     *
     * 兜底策略：
     * - 优先使用回放 length（秒）；
     * - 无 length 时回退直播预计时长（分钟转秒）；
     * - 都不存在返回 0。
     *
     * @param object $row
     * @return int
     */
    protected function buildReplayDuration($row): int
    {
        $duration = (int)($row->playback_length ?? 0);
        if ($duration > 0) {
            return $duration;
        }

        $liveDuration = (int)($row->live_duration ?? 0);
        if ($liveDuration > 0) {
            return $liveDuration * 60;
        }

        return 0;
    }

    /**
     * 生成封面地址，优先使用主封面，缺失时回退备用封面。
     *
     * @param string $primary
     * @param string $fallback
     * @return string
     */
    protected function buildCoverUrl(string $primary, string $fallback = ''): string
    {
        $cover = trim($primary) !== '' ? $primary : $fallback;
        return $this->normalizeUrl($cover);
    }

    /**
     * 规范化 URL。
     *
     * 规则：
     * - http/https 地址直接透传；
     * - 相对路径统一走文件服务转完整 URL；
     * - 空值返回空串。
     *
     * @param string $value
     * @return string
     */
    protected function normalizeUrl(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (stripos($value, 'http://') === 0 || stripos($value, 'https://') === 0) {
            return $value;
        }

        return $this->fileUploadService->generateFileUrl($value);
    }

    /**
     * 统一格式化时间输出。
     *
     * @param mixed $value
     * @return string
     */
    protected function formatDateTime($value): string
    {
        if (!$value) {
            return '';
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * 计算开播时间与当前时间的绝对秒差。
     *
     * @param mixed $startTime
     * @param int $nowTimestamp
     * @return int
     */
    protected function distanceToNowSeconds($startTime, int $nowTimestamp): int
    {
        if (!$startTime) {
            return PHP_INT_MAX;
        }

        try {
            return abs(Carbon::parse($startTime)->timestamp - $nowTimestamp);
        } catch (\Exception $e) {
            return PHP_INT_MAX;
        }
    }
}
