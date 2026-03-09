<?php

namespace App\Http\Controllers\App;

use App\Constant\AppResponseCode;
use App\Http\Controllers\Controller;
use App\Http\Resources\App\AppApiResponse;
use App\Models\App\AppLiveRoom;
use App\Models\App\AppLiveRoomReserve;
use App\Models\App\AppLiveRoomStat;
use App\Services\AppFileUploadService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * App 端直播首页与预约控制器。
 *
 * 职责：
 * 1. 输出直播首页 latest 与分 tab 列表；
 * 2. 处理会员对直播间的预约/取消预约；
 * 3. 统一组装直播卡片字段，保证首页与操作返回口径一致。
 */
class LiveCourseController extends Controller
{
    const TAB_UPCOMING = 'upcoming';
    const TAB_REPLAY = 'replay';
    const DEFAULT_PAGE_SIZE = 20;
    const MAX_PAGE_SIZE = 100;

    /**
     * @var AppFileUploadService
     */
    protected $fileUploadService;

    public function __construct(AppFileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * 获取当前登录会员ID
     *
     * @param Request $request
     * @return int
     */
    protected function getMemberId(Request $request): int
    {
        return (int)$request->attributes->get('member_id', 0);
    }

    /**
     * 获取直播首页数据。
     *
     * 接口用途：
     * - App 直播页按 tab 返回预告/回放列表，并返回顶部 latest 卡片。
     *
     * 关键输入：
     * - tab：`upcoming`（预告）或 `replay`（回放）；
     * - page/pageSize：分页参数，pageSize 使用固定上限保护。
     *
     * 关键输出：
     * - id 使用直播间 `room_id`；
     * - 预告/直播中场景 `reserveCount/isReserved` 来自直播域数据表；
     * - 回放场景 `replayUrl` 当前固定返回空串。
     *
     * 失败策略：
     * - 参数非法返回业务错误；
     * - 运行异常仅记录日志并返回通用服务错误，避免暴露内部细节。
     */
    public function home(Request $request)
    {
        $tab = strtolower((string)$request->input('tab', ''));
        if (!in_array($tab, [self::TAB_UPCOMING, self::TAB_REPLAY], true)) {
            return AppApiResponse::error('tab 参数错误', AppResponseCode::INVALID_PARAMS);
        }

        $page = max((int)$request->input('page', 1), 1);
        $pageSize = (int)$request->input('pageSize', self::DEFAULT_PAGE_SIZE);
        if ($pageSize <= 0) {
            $pageSize = self::DEFAULT_PAGE_SIZE;
        }
        $pageSize = min($pageSize, self::MAX_PAGE_SIZE);

        $memberId = $this->getMemberId($request);

        try {
            $listQuery = $this->buildHomeBaseQuery($memberId);
            $this->applyTabFilter($listQuery, $tab);
            $paginator = $listQuery->paginate($pageSize, ['*'], 'page', $page);

            $latestRow = $this->getLatestLiveRow($memberId);
            $rows = collect($paginator->items());
            $list = $rows->map(function ($row) {
                return $this->formatLiveItem($row);
            })->values()->all();

            return AppApiResponse::success([
                'data' => [
                    'latest' => $latestRow ? $this->formatLiveItem($latestRow) : null,
                    'tab' => $tab,
                    'list' => $list,
                    'total' => $paginator->total(),
                    'page' => $paginator->currentPage(),
                    'pageSize' => $paginator->perPage(),
                    'hasMore' => $paginator->currentPage() < $paginator->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('获取直播首页数据失败', [
                'tab' => $tab,
                'page' => $page,
                'pageSize' => $pageSize,
                'member_id' => $memberId,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 预约直播。
     *
     * 关键规则：
     * 1. liveId 字段保持兼容，但语义已切换为直播间ID（room_id）；
     * 2. 幂等键为 member_id + room_id，同一用户重复调用不重复累加预约数；
     * 3. 仅在首次预约或从取消态恢复时增加 reserveCount。
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reserve(Request $request)
    {
        $liveId = (int)$request->input('liveId', 0);
        if ($liveId <= 0) {
            return AppApiResponse::error('liveId 参数错误', AppResponseCode::INVALID_PARAMS);
        }

        $memberId = $this->getMemberId($request);
        if ($memberId <= 0) {
            return AppApiResponse::unauthorized();
        }

        try {
            $reserveCount = DB::transaction(function () use ($memberId, $liveId) {
                return $this->reserveLiveRoom($memberId, $liveId);
            });

            return AppApiResponse::success([
                'data' => [
                    'liveId' => $liveId,
                    'isReserved' => true,
                    'reserveCount' => $reserveCount,
                ],
            ]);
        } catch (\DomainException $e) {
            return AppApiResponse::dataNotFound($e->getMessage());
        } catch (\Exception $e) {
            Log::error('预约直播失败', [
                'room_id' => $liveId,
                'member_id' => $memberId,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 取消预约直播。
     *
     * 关键规则：
     * 1. liveId 字段保持兼容，但语义已切换为直播间ID（room_id）；
     * 2. 幂等键为 member_id + room_id，重复取消不会重复扣减 reserveCount；
     * 3. 无预约记录或已取消记录按幂等成功处理，不抛错。
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function unreserve(Request $request)
    {
        $liveId = (int)$request->input('liveId', 0);
        if ($liveId <= 0) {
            return AppApiResponse::error('liveId 参数错误', AppResponseCode::INVALID_PARAMS);
        }

        $memberId = $this->getMemberId($request);
        if ($memberId <= 0) {
            return AppApiResponse::unauthorized();
        }

        try {
            $reserveCount = DB::transaction(function () use ($memberId, $liveId) {
                return $this->unreserveLiveRoom($memberId, $liveId);
            });

            return AppApiResponse::success([
                'data' => [
                    'liveId' => $liveId,
                    'isReserved' => false,
                    'reserveCount' => $reserveCount,
                ],
            ]);
        } catch (\DomainException $e) {
            return AppApiResponse::dataNotFound($e->getMessage());
        } catch (\Exception $e) {
            Log::error('取消预约直播失败', [
                'room_id' => $liveId,
                'member_id' => $memberId,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 构建直播首页基础查询（直播表直查）。
     *
     * 设计约束：
     * - 仅依赖直播域数据表 `app_live_room/app_live_room_stat`；
     * - 登录态下追加 member_id 维度的预约态查询，未登录统一返回未预约；
     * - 仅返回启用且未删除的直播间；
     * - 统一生成 start_time 与 replay_sort_time，避免各分支重复拼装排序字段。
     *
     * @param int $memberId
     */
    protected function buildHomeBaseQuery(int $memberId)
    {
        $query = DB::table('app_live_room as r')
            ->leftJoin('app_live_room_stat as rs', 'rs.room_id', '=', 'r.room_id')
            ->whereNull('r.deleted_at')
            ->where('r.status', AppLiveRoom::STATUS_ENABLED);

        $selectFields = [
            'r.room_id as id',
            'r.room_title as title',
            'r.room_cover',
            'r.live_status',
            'r.live_duration',
            'r.scheduled_start_time',
            'r.scheduled_end_time',
            'r.actual_start_time',
            'r.actual_end_time',
            'r.created_at',
            'rs.total_viewer_count',
            DB::raw('COALESCE(rs.reserve_count, 0) as reserve_count'),
            DB::raw('COALESCE(r.actual_start_time, r.scheduled_start_time) as start_time'),
            DB::raw('COALESCE(r.actual_end_time, r.scheduled_end_time, r.actual_start_time, r.scheduled_start_time, r.created_at) as replay_sort_time'),
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
     * 应用 tab 查询条件
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param string $tab
     */
    protected function applyTabFilter($query, string $tab): void
    {
        if ($tab === self::TAB_UPCOMING) {
            $query->whereIn('r.live_status', [
                AppLiveRoom::LIVE_STATUS_LIVING,
                AppLiveRoom::LIVE_STATUS_NOT_STARTED,
            ])->orderByRaw(
                'CASE WHEN r.live_status = ? THEN 0 ELSE 1 END',
                [AppLiveRoom::LIVE_STATUS_LIVING]
            )->orderBy('start_time', 'asc')
                ->orderByDesc('r.room_id');

            return;
        }

        $query->whereIn('r.live_status', [
            AppLiveRoom::LIVE_STATUS_ENDED,
            AppLiveRoom::LIVE_STATUS_CANCELLED,
        ])->orderBy('replay_sort_time', 'desc')
            ->orderByDesc('r.room_id');
    }

    /**
     * 获取顶部 latest 卡片
     *
     * 优先级：
     * 1. 直播中/未开始；
     * 2. 已结束/已取消；
     * 3. 任意最近一条。
     *
     * @param int $memberId
     */
    protected function getLatestLiveRow(int $memberId)
    {
        $activeRow = $this->buildHomeBaseQuery($memberId)
            ->whereIn('r.live_status', [
                AppLiveRoom::LIVE_STATUS_LIVING,
                AppLiveRoom::LIVE_STATUS_NOT_STARTED,
            ])
            ->orderByRaw(
                'CASE WHEN r.live_status = ? THEN 0 ELSE 1 END',
                [AppLiveRoom::LIVE_STATUS_LIVING]
            )
            ->orderBy('start_time', 'asc')
            ->orderByDesc('r.room_id')
            ->first();

        if ($activeRow) {
            return $activeRow;
        }

        $replayRow = $this->buildHomeBaseQuery($memberId)
            ->whereIn('r.live_status', [
                AppLiveRoom::LIVE_STATUS_ENDED,
                AppLiveRoom::LIVE_STATUS_CANCELLED,
            ])
            ->orderBy('replay_sort_time', 'desc')
            ->orderByDesc('r.room_id')
            ->first();

        if ($replayRow) {
            return $replayRow;
        }

        return $this->buildHomeBaseQuery($memberId)
            ->orderBy('replay_sort_time', 'desc')
            ->orderByDesc('r.room_id')
            ->first();
    }

    /**
     * 执行直播间预约并返回最新预约人数。
     *
     * 关键规则：
     * 1. 事务内按 room_id 加锁，保证并发下 reserve_count 不会重复累加；
     * 2. 幂等键为 member_id + room_id，重复预约直接返回当前人数；
     * 3. 取消后再次预约（status:2->1）会刷新 created_at 为最新预约时间。
     *
     * @param int $memberId
     * @param int $roomId
     * @return int
     */
    protected function reserveLiveRoom(int $memberId, int $roomId): int
    {
        $room = $this->lockReservableRoom($roomId);
        if (!$room) {
            throw new \DomainException('直播不存在');
        }

        $reserve = AppLiveRoomReserve::query()
            ->where('member_id', $memberId)
            ->where('room_id', $roomId)
            ->lockForUpdate()
            ->first();

        $reserveTime = now();
        $shouldIncrease = false;

        if (!$reserve) {
            $inserted = DB::table('app_live_room_reserve')->insertOrIgnore([
                'member_id' => $memberId,
                'room_id' => $roomId,
                'status' => AppLiveRoomReserve::STATUS_RESERVED,
                'created_at' => $reserveTime,
                'updated_at' => $reserveTime,
            ]);

            // 只有真正写入新预约记录时才累加人数，避免并发重复请求导致计数膨胀。
            $shouldIncrease = $inserted > 0;
            $reserve = AppLiveRoomReserve::query()
                ->where('member_id', $memberId)
                ->where('room_id', $roomId)
                ->lockForUpdate()
                ->first();
        }

        if ($reserve && !$shouldIncrease && (int)$reserve->status === AppLiveRoomReserve::STATUS_CANCELLED) {
            // 取消后再次预约视作“新预约”，created_at 刷新为最新预约时间便于前端展示。
            $reserve->status = AppLiveRoomReserve::STATUS_RESERVED;
            $reserve->created_at = $reserveTime;
            $reserve->updated_at = $reserveTime;
            $reserve->save();
            $shouldIncrease = true;
        }

        $stat = $this->lockOrCreateLiveRoomStat($roomId);
        if ($shouldIncrease) {
            $stat->reserve_count = (int)$stat->reserve_count + 1;
            $stat->save();
        }

        return (int)$stat->reserve_count;
    }

    /**
     * 执行直播间取消预约并返回最新预约人数。
     *
     * 失败策略：
     * - 无预约记录或已取消记录按幂等成功处理，不抛错；
     * - 仅当原状态为预约中时才扣减 reserve_count；
     * - reserve_count 始终做下限保护，避免出现负数。
     *
     * @param int $memberId
     * @param int $roomId
     * @return int
     */
    protected function unreserveLiveRoom(int $memberId, int $roomId): int
    {
        $room = $this->lockReservableRoom($roomId);
        if (!$room) {
            throw new \DomainException('直播不存在');
        }

        $reserve = AppLiveRoomReserve::query()
            ->where('member_id', $memberId)
            ->where('room_id', $roomId)
            ->lockForUpdate()
            ->first();

        $stat = $this->lockOrCreateLiveRoomStat($roomId);

        // 无预约记录或已取消记录直接返回当前统计，避免重复扣减。
        if (!$reserve || (int)$reserve->status === AppLiveRoomReserve::STATUS_CANCELLED) {
            return (int)$stat->reserve_count;
        }

        $reserve->status = AppLiveRoomReserve::STATUS_CANCELLED;
        $reserve->save();

        if ((int)$stat->reserve_count > 0) {
            $stat->reserve_count = (int)$stat->reserve_count - 1;
            $stat->save();
        }

        return (int)$stat->reserve_count;
    }

    /**
     * 锁定可预约直播间。
     *
     * 约束：
     * - 仅允许启用且未删除直播间进入预约链路；
     * - 事务中加锁，保证状态校验与后续写入一致。
     *
     * @param int $roomId
     * @return AppLiveRoom|null
     */
    protected function lockReservableRoom(int $roomId): ?AppLiveRoom
    {
        return AppLiveRoom::query()
            ->select(['room_id', 'status'])
            ->where('room_id', $roomId)
            ->where('status', AppLiveRoom::STATUS_ENABLED)
            ->whereNull('deleted_at')
            ->lockForUpdate()
            ->first();
    }

    /**
     * 锁定直播间统计记录，不存在时自动初始化。
     *
     * @param int $roomId
     * @return AppLiveRoomStat
     */
    protected function lockOrCreateLiveRoomStat(int $roomId): AppLiveRoomStat
    {
        $stat = AppLiveRoomStat::query()
            ->where('room_id', $roomId)
            ->lockForUpdate()
            ->first();
        if ($stat) {
            return $stat;
        }

        $now = now();
        // 先插入默认记录再加锁读取，避免并发初始化时出现唯一索引冲突。
        DB::table('app_live_room_stat')->insertOrIgnore([
            'room_id' => $roomId,
            'reserve_count' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $stat = AppLiveRoomStat::query()
            ->where('room_id', $roomId)
            ->lockForUpdate()
            ->first();
        if (!$stat) {
            throw new \RuntimeException('初始化直播间统计失败');
        }

        return $stat;
    }

    /**
     * 格式化单条直播数据
     *
     * @param object $row
     * @return array
     */
    protected function formatLiveItem($row): array
    {
        $status = $this->mapLiveStatus($row);
        $isReserved = (int)($row->is_reserved ?? 0) === 1;

        $item = [
            'id' => (int)($row->id ?? 0),
            'title' => (string)($row->title ?? ''),
            'cover' => $this->buildCoverUrl($row),
            'startTime' => $this->formatDateTime($row->start_time ?? null),
            'status' => $status,
            'actionText' => $this->buildActionText($status, $isReserved),
        ];

        if ($status === 'replay') {
            $item['watchCount'] = $this->buildWatchCount($row);
            $item['durationSec'] = $this->buildDurationSec($row);
            // 直播表直查阶段先返回空串，后续接回放聚合链路再补齐地址。
            $item['replayUrl'] = '';

            return $item;
        }

        $item['reserveCount'] = (int)($row->reserve_count ?? 0);
        $item['isReserved'] = $isReserved;

        return $item;
    }

    /**
     * 构建封面 URL
     *
     * 优先级：
     * 1. 直播间封面 room_cover；
     * 2. 历史兼容字段 live_cover/chapter_cover/course_cover（仅兜底）。
     */
    protected function buildCoverUrl($row): string
    {
        $cover = (string)(($row->room_cover ?? '') ?: (($row->live_cover ?? '') ?: (($row->chapter_cover ?? '') ?: (($row->course_cover ?? '') ?: ''))));
        if ($cover === '') {
            return '';
        }

        if (stripos($cover, 'http://') === 0 || stripos($cover, 'https://') === 0) {
            return $cover;
        }

        return $this->fileUploadService->generateFileUrl($cover);
    }

    /**
     * 映射状态到接口状态值
     */
    protected function mapLiveStatus($row): string
    {
        $liveStatus = (int)($row->live_status ?? AppLiveRoom::LIVE_STATUS_NOT_STARTED);

        if ($liveStatus === AppLiveRoom::LIVE_STATUS_LIVING) {
            return 'live';
        }

        if ($liveStatus === AppLiveRoom::LIVE_STATUS_NOT_STARTED) {
            return 'upcoming';
        }

        if (in_array($liveStatus, [
            AppLiveRoom::LIVE_STATUS_ENDED,
            AppLiveRoom::LIVE_STATUS_CANCELLED,
        ], true)) {
            return 'replay';
        }

        return 'ended';
    }

    /**
     * 按状态生成按钮文案
     */
    protected function buildActionText(string $status, bool $isReserved): string
    {
        if ($status === 'replay') {
            return '回放';
        }

        if ($status === 'live') {
            return '直播中';
        }

        if ($status === 'ended') {
            return '已结束';
        }

        return $isReserved ? '已预约' : '预约';
    }

    /**
     * 观看人数（回放场景）
     */
    protected function buildWatchCount($row): int
    {
        return (int)($row->total_viewer_count ?? 0);
    }

    /**
     * 回放时长（秒）
     */
    protected function buildDurationSec($row): int
    {
        $duration = (int)($row->live_duration ?? 0);
        if ($duration > 0) {
            // live_duration 单位为分钟，接口返回秒。
            return $duration * 60;
        }

        try {
            if (!empty($row->actual_start_time) && !empty($row->actual_end_time)) {
                return Carbon::parse($row->actual_end_time)->diffInSeconds(Carbon::parse($row->actual_start_time));
            }

            if (!empty($row->scheduled_start_time) && !empty($row->scheduled_end_time)) {
                return Carbon::parse($row->scheduled_end_time)->diffInSeconds(Carbon::parse($row->scheduled_start_time));
            }
        } catch (\Exception $e) {
            // 数据异常时返回 0
        }

        return 0;
    }

    /**
     * 格式化时间
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
}
