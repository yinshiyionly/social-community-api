<?php

namespace App\Http\Controllers\App;

use App\Constant\AppResponseCode;
use App\Http\Controllers\Controller;
use App\Http\Resources\App\AppApiResponse;
use App\Models\App\AppCourseBase;
use App\Models\App\AppCourseChapter;
use App\Models\App\AppLiveRoom;
use App\Models\App\AppMemberCourse;
use App\Models\App\AppMemberSchedule;
use App\Services\App\LearningCenterService;
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

    /**
     * @var LearningCenterService
     */
    protected $learningCenterService;

    public function __construct(
        AppFileUploadService $fileUploadService,
        LearningCenterService $learningCenterService
    )
    {
        $this->fileUploadService = $fileUploadService;
        $this->learningCenterService = $learningCenterService;
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
     * - 预告场景 `reserveCount/isReserved` 为占位值 `0/false`；
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
            $listQuery = $this->buildHomeBaseQuery();
            $this->applyTabFilter($listQuery, $tab);
            $paginator = $listQuery->paginate($pageSize, ['*'], 'page', $page);

            $latestRow = $this->getLatestLiveRow();
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
     * 1. 当前仍使用课程章节维度预约，liveId 传章节ID（chapter_id）；
     * 2. 幂等键为 member_id + course_id，同一用户重复调用不重复累加预约数；
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

        $liveCourse = $this->findReserveLiveCourse($liveId);
        if (!$liveCourse) {
            return AppApiResponse::dataNotFound('直播不存在');
        }

        try {
            $reserveCount = DB::transaction(function () use ($memberId, $liveCourse) {
                return $this->reserveLiveCourse($memberId, (int)$liveCourse->course_id);
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
                'live_id' => $liveId,
                'member_id' => $memberId,
                'course_id' => (int)$liveCourse->course_id,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 取消预约直播。
     *
     * 关键规则：
     * 1. 当前仍使用课程章节维度预约，liveId 传章节ID（chapter_id）；
     * 2. 幂等键为 member_id + course_id，重复取消不会重复扣减 reserveCount；
     * 3. 仅允许取消“预约产生”的记录，不影响购买/领取课程。
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

        $liveCourse = $this->findReserveLiveCourse($liveId);
        if (!$liveCourse) {
            return AppApiResponse::dataNotFound('直播不存在');
        }

        try {
            $reserveCount = DB::transaction(function () use ($memberId, $liveCourse) {
                return $this->unreserveLiveCourse($memberId, (int)$liveCourse->course_id);
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
        } catch (\LogicException $e) {
            return AppApiResponse::error($e->getMessage(), AppResponseCode::BUSINESS_ERROR);
        } catch (\Exception $e) {
            Log::error('取消预约直播失败', [
                'live_id' => $liveId,
                'member_id' => $memberId,
                'course_id' => (int)$liveCourse->course_id,
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
     * - 仅返回启用且未删除的直播间；
     * - 统一生成 start_time 与 replay_sort_time，避免各分支重复拼装排序字段。
     */
    protected function buildHomeBaseQuery()
    {
        return DB::table('app_live_room as r')
            ->leftJoin('app_live_room_stat as rs', 'rs.room_id', '=', 'r.room_id')
            ->whereNull('r.deleted_at')
            ->where('r.status', AppLiveRoom::STATUS_ENABLED)
            ->select([
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
                DB::raw('COALESCE(r.actual_start_time, r.scheduled_start_time) as start_time'),
                DB::raw('COALESCE(r.actual_end_time, r.scheduled_end_time, r.actual_start_time, r.scheduled_start_time, r.created_at) as replay_sort_time'),
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
     */
    protected function getLatestLiveRow()
    {
        $activeRow = $this->buildHomeBaseQuery()
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

        $replayRow = $this->buildHomeBaseQuery()
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

        return $this->buildHomeBaseQuery()
            ->orderBy('replay_sort_time', 'desc')
            ->orderByDesc('r.room_id')
            ->first();
    }

    /**
     * 根据直播ID查找可预约的课程。
     *
     * @param int $liveId
     * @return object|null
     */
    protected function findReserveLiveCourse(int $liveId)
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
            ->whereNull('ch.deleted_at')
            ->where('ch.chapter_id', $liveId)
            ->where('ch.status', AppCourseChapter::STATUS_ONLINE)
            ->where('c.play_type', AppCourseBase::PLAY_TYPE_LIVE)
            ->where('c.status', AppCourseBase::STATUS_ONLINE)
            ->select([
                'ch.chapter_id as live_id',
                'ch.course_id',
            ])
            ->first();
    }

    /**
     * 执行直播预约并返回最新预约人数。
     *
     * 约束：
     * 1. 事务内对课程记录加锁，避免并发下 enroll_count 被重复累加；
     * 2. 软删或过期记录恢复后视为重新预约；
     * 3. 首次预约成功后补齐课表数据，保持与课程领取链路一致。
     *
     * @param int $memberId
     * @param int $courseId
     * @return int
     */
    protected function reserveLiveCourse(int $memberId, int $courseId): int
    {
        $course = AppCourseBase::query()
            ->select(['course_id', 'valid_days', 'total_chapter', 'enroll_count'])
            ->where('course_id', $courseId)
            ->where('status', AppCourseBase::STATUS_ONLINE)
            ->where('play_type', AppCourseBase::PLAY_TYPE_LIVE)
            ->lockForUpdate()
            ->first();
        if (!$course) {
            throw new \DomainException('直播不存在');
        }

        $memberCourse = AppMemberCourse::withTrashed()
            ->where('member_id', $memberId)
            ->where('course_id', $courseId)
            ->lockForUpdate()
            ->first();

        $enrollTime = now();
        $expireTime = null;
        if ((int)$course->valid_days > 0) {
            $expireTime = $enrollTime->copy()->addDays((int)$course->valid_days);
        }

        $shouldIncrease = false;
        $isFirstCreate = false;

        if (!$memberCourse) {
            $memberCourse = AppMemberCourse::query()->create([
                'member_id' => $memberId,
                'course_id' => $courseId,
                'source_type' => AppMemberCourse::SOURCE_TYPE_ACTIVITY,
                'paid_amount' => 0,
                'enroll_time' => $enrollTime,
                'expire_time' => $expireTime,
                'is_expired' => 0,
                'total_chapters' => (int)$course->total_chapter,
            ]);
            $shouldIncrease = true;
            $isFirstCreate = true;
        } elseif ($memberCourse->trashed() || (int)$memberCourse->is_expired === 1) {
            // 仅在非有效预约状态下恢复，避免重复调用导致计数膨胀。
            if ($memberCourse->trashed()) {
                $memberCourse->restore();
            }
            $memberCourse->is_expired = 0;
            $memberCourse->enroll_time = $enrollTime;
            $memberCourse->expire_time = $expireTime;
            $memberCourse->save();
            $shouldIncrease = true;
        }

        if ($shouldIncrease) {
            $course->enroll_count = (int)$course->enroll_count + 1;
            $course->save();

            if ($isFirstCreate) {
                $this->learningCenterService->generateSchedule(
                    $memberId,
                    $courseId,
                    (int)$memberCourse->id,
                    $enrollTime
                );
            } else {
                // 取消预约后会软删课表，恢复预约时需要一并恢复，避免首页显示“已预约”但课表缺失。
                $this->restoreMemberCourseSchedule((int)$memberCourse->id);
            }
        }

        return (int)$course->enroll_count;
    }

    /**
     * 执行取消预约并返回最新预约人数。
     *
     * 失败策略：
     * - 无预约记录或已取消记录按幂等成功处理，不抛错；
     * - 非预约来源（购买/领取）拒绝取消，避免误删已购课程关系。
     *
     * @param int $memberId
     * @param int $courseId
     * @return int
     */
    protected function unreserveLiveCourse(int $memberId, int $courseId): int
    {
        $course = AppCourseBase::query()
            ->select(['course_id', 'enroll_count', 'status', 'play_type'])
            ->where('course_id', $courseId)
            ->where('status', AppCourseBase::STATUS_ONLINE)
            ->where('play_type', AppCourseBase::PLAY_TYPE_LIVE)
            ->lockForUpdate()
            ->first();
        if (!$course) {
            throw new \DomainException('直播不存在');
        }

        $memberCourse = AppMemberCourse::withTrashed()
            ->where('member_id', $memberId)
            ->where('course_id', $courseId)
            ->lockForUpdate()
            ->first();

        // 无记录、软删记录或过期记录都视为“已取消”，直接返回当前人数。
        if (!$memberCourse || $memberCourse->trashed() || (int)$memberCourse->is_expired === 1) {
            return (int)$course->enroll_count;
        }

        if ((int)$memberCourse->source_type !== AppMemberCourse::SOURCE_TYPE_ACTIVITY) {
            throw new \LogicException('当前直播不支持取消预约');
        }

        $memberCourse->is_expired = 1;
        $memberCourse->expire_time = now();
        $memberCourse->save();
        $memberCourse->delete();

        AppMemberSchedule::query()
            ->where('member_course_id', $memberCourse->id)
            ->delete();

        // 计数下限保护，避免异常数据导致出现负数。
        if ((int)$course->enroll_count > 0) {
            $course->enroll_count = (int)$course->enroll_count - 1;
            $course->save();
        }

        return (int)$course->enroll_count;
    }

    /**
     * 恢复用户课程对应课表（用于取消后再次预约）。
     *
     * @param int $memberCourseId
     * @return void
     */
    protected function restoreMemberCourseSchedule(int $memberCourseId): void
    {
        AppMemberSchedule::withTrashed()
            ->where('member_course_id', $memberCourseId)
            ->whereNotNull('deleted_at')
            ->restore();
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

        $item = [
            'id' => (int)($row->id ?? 0),
            'title' => (string)($row->title ?? ''),
            'cover' => $this->buildCoverUrl($row),
            'startTime' => $this->formatDateTime($row->start_time ?? null),
            'status' => $status,
            'actionText' => $this->buildActionText($status, false),
        ];

        if ($status === 'replay') {
            $item['watchCount'] = $this->buildWatchCount($row);
            $item['durationSec'] = $this->buildDurationSec($row);
            // 直播表直查阶段先返回空串，后续接回放聚合链路再补齐地址。
            $item['replayUrl'] = '';

            return $item;
        }

        // 当前首页不再关联课程预约关系，预约字段先使用占位值。
        $item['reserveCount'] = 0;
        $item['isReserved'] = false;

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
