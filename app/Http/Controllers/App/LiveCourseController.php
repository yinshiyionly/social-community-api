<?php

namespace App\Http\Controllers\App;

use App\Constant\AppResponseCode;
use App\Http\Controllers\Controller;
use App\Http\Resources\App\AppApiResponse;
use App\Models\App\AppChapterContentLive;
use App\Models\App\AppCourseBase;
use App\Models\App\AppCourseChapter;
use App\Services\AppFileUploadService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 直播控制器
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
     * 获取直播首页数据
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
            $listQuery = $this->buildHomeBaseQuery();
            $this->applyTabFilter($listQuery, $tab);
            $paginator = $listQuery->paginate($pageSize, ['*'], 'page', $page);

            $latestRow = $this->getLatestLiveRow();
            $rows = collect($paginator->items());

            $courseIds = $rows->pluck('course_id');
            if ($latestRow && isset($latestRow->course_id)) {
                $courseIds->push((int)$latestRow->course_id);
            }
            $reservedCourseIds = $this->getReservedCourseIds($memberId, $courseIds->unique()->values()->all());

            $list = [];
            foreach ($rows as $row) {
                $list[] = $this->formatLiveItem($row, $reservedCourseIds);
            }

            return AppApiResponse::success([
                'data' => [
                    'latest' => $latestRow ? $this->formatLiveItem($latestRow, $reservedCourseIds) : null,
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
     * 构建直播首页基础查询
     */
    protected function buildHomeBaseQuery()
    {
        return DB::table('app_course_chapter as ch')
            ->join('app_course_base as c', function ($join) {
                $join->on('c.course_id', '=', 'ch.course_id')
                    ->whereNull('c.deleted_at');
            })
            ->join('app_chapter_content_live as l', function ($join) {
                $join->on('l.chapter_id', '=', 'ch.chapter_id')
                    ->whereNull('l.deleted_at');
            })
            ->leftJoin('app_live_room_stat as rs', 'rs.room_id', '=', 'l.live_room_id')
            ->whereNull('ch.deleted_at')
            ->where('ch.status', AppCourseChapter::STATUS_ONLINE)
            ->where('c.play_type', AppCourseBase::PLAY_TYPE_LIVE)
            ->where('c.status', AppCourseBase::STATUS_ONLINE)
            ->select([
                'ch.chapter_id as id',
                'ch.course_id as course_id',
                'ch.chapter_title as title',
                'ch.cover_image as chapter_cover',
                'ch.chapter_start_time',
                'ch.chapter_end_time',
                'ch.view_count as chapter_view_count',
                'c.cover_image as course_cover',
                'c.enroll_count as course_enroll_count',
                'c.view_count as course_view_count',
                'l.live_cover',
                'l.live_start_time',
                'l.live_end_time',
                'l.live_status',
                'l.has_playback',
                'l.playback_url',
                'l.playback_duration',
                'l.live_room_id',
                'rs.total_viewer_count',
                DB::raw('COALESCE(l.live_start_time, ch.chapter_start_time) as start_time'),
            ]);
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
            $query->whereIn('l.live_status', [
                AppChapterContentLive::LIVE_STATUS_LIVING,
                AppChapterContentLive::LIVE_STATUS_NOT_STARTED,
            ])->orderByRaw(
                'CASE WHEN l.live_status = ? THEN 0 ELSE 1 END',
                [AppChapterContentLive::LIVE_STATUS_LIVING]
            )->orderBy('start_time', 'asc')
                ->orderBy('ch.chapter_id', 'desc');

            return;
        }

        $query->whereNotIn('l.live_status', [
            AppChapterContentLive::LIVE_STATUS_NOT_STARTED,
            AppChapterContentLive::LIVE_STATUS_LIVING,
        ])->where(function ($q) {
            $q->where('l.has_playback', 1)
                ->orWhere(function ($sub) {
                    $sub->whereNotNull('l.playback_url')
                        ->where('l.playback_url', '<>', '');
                });
        })->orderBy('start_time', 'desc')
            ->orderBy('ch.chapter_id', 'desc');
    }

    /**
     * 获取顶部 latest 卡片
     *
     * 优先：直播中/未开始；其次：有回放；最后：任意最近一条。
     */
    protected function getLatestLiveRow()
    {
        $activeRow = $this->buildHomeBaseQuery()
            ->whereIn('l.live_status', [
                AppChapterContentLive::LIVE_STATUS_LIVING,
                AppChapterContentLive::LIVE_STATUS_NOT_STARTED,
            ])
            ->orderByRaw(
                'CASE WHEN l.live_status = ? THEN 0 ELSE 1 END',
                [AppChapterContentLive::LIVE_STATUS_LIVING]
            )
            ->orderBy('start_time', 'asc')
            ->orderBy('ch.chapter_id', 'desc')
            ->first();

        if ($activeRow) {
            return $activeRow;
        }

        $replayRow = $this->buildHomeBaseQuery()
            ->whereNotIn('l.live_status', [
                AppChapterContentLive::LIVE_STATUS_NOT_STARTED,
                AppChapterContentLive::LIVE_STATUS_LIVING,
            ])->where(function ($q) {
                $q->where('l.has_playback', 1)
                    ->orWhere(function ($sub) {
                        $sub->whereNotNull('l.playback_url')
                            ->where('l.playback_url', '<>', '');
                    });
            })
            ->orderBy('start_time', 'desc')
            ->orderBy('ch.chapter_id', 'desc')
            ->first();

        if ($replayRow) {
            return $replayRow;
        }

        return $this->buildHomeBaseQuery()
            ->orderBy('start_time', 'desc')
            ->orderBy('ch.chapter_id', 'desc')
            ->first();
    }

    /**
     * 获取当前用户已预约（已拥有课程）列表
     *
     * @param int $memberId
     * @param array $courseIds
     * @return array
     */
    protected function getReservedCourseIds(int $memberId, array $courseIds): array
    {
        if ($memberId <= 0 || empty($courseIds)) {
            return [];
        }

        return DB::table('app_member_course')
            ->where('member_id', $memberId)
            ->where('is_expired', 0)
            ->whereNull('deleted_at')
            ->whereIn('course_id', $courseIds)
            ->pluck('course_id')
            ->map(function ($id) {
                return (int)$id;
            })
            ->all();
    }

    /**
     * 格式化单条直播数据
     *
     * @param object $row
     * @param array $reservedCourseIds
     * @return array
     */
    protected function formatLiveItem($row, array $reservedCourseIds): array
    {
        $status = $this->mapLiveStatus($row);
        $courseId = (int)($row->course_id ?? 0);
        $isReserved = in_array($courseId, $reservedCourseIds, true);

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
            $item['replayUrl'] = (string)($row->playback_url ?? '');

            return $item;
        }

        $item['reserveCount'] = (int)($row->course_enroll_count ?? 0);
        $item['isReserved'] = $isReserved;

        return $item;
    }

    /**
     * 构建封面 URL
     */
    protected function buildCoverUrl($row): string
    {
        $cover = (string)(($row->live_cover ?? '') ?: (($row->chapter_cover ?? '') ?: (($row->course_cover ?? '') ?: '')));
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
        $liveStatus = (int)($row->live_status ?? AppChapterContentLive::LIVE_STATUS_NOT_STARTED);
        $hasReplay = (int)($row->has_playback ?? 0) === 1 || !empty($row->playback_url);

        if ($liveStatus === AppChapterContentLive::LIVE_STATUS_LIVING) {
            return 'live';
        }

        if ($liveStatus === AppChapterContentLive::LIVE_STATUS_NOT_STARTED) {
            return 'upcoming';
        }

        if ($hasReplay) {
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
        $watchCount = $row->total_viewer_count ?? null;
        if ($watchCount !== null) {
            return (int)$watchCount;
        }

        if ($row->chapter_view_count !== null) {
            return (int)$row->chapter_view_count;
        }

        return (int)($row->course_view_count ?? 0);
    }

    /**
     * 回放时长（秒）
     */
    protected function buildDurationSec($row): int
    {
        $duration = (int)($row->playback_duration ?? 0);
        if ($duration > 0) {
            return $duration;
        }

        try {
            if (!empty($row->live_start_time) && !empty($row->live_end_time)) {
                return Carbon::parse($row->live_end_time)->diffInSeconds(Carbon::parse($row->live_start_time));
            }

            if (!empty($row->chapter_start_time) && !empty($row->chapter_end_time)) {
                return Carbon::parse($row->chapter_end_time)->diffInSeconds(Carbon::parse($row->chapter_start_time));
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
