<?php

namespace App\Http\Controllers\App;

use App\Constant\AppResponseCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\LiveHomeRequest;
use App\Http\Resources\App\AppApiResponse;
use App\Http\Resources\App\LiveHomeResource;
use App\Models\App\AppLiveRoom;
use App\Models\App\AppLiveRoomReserve;
use App\Models\App\AppLiveRoomStat;
use App\Services\App\LiveHomeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * App 端直播首页与预约控制器。
 *
 * 职责：
 * 1. 调用服务层输出直播首页 latest 与分 tab 列表；
 * 2. 处理会员对直播间的预约/取消预约；
 * 3. 在控制器层统一处理接口响应与异常日志。
 */
class LiveCourseController extends Controller
{
    /**
     * @var LiveHomeService
     */
    protected $liveHomeService;

    public function __construct(LiveHomeService $liveHomeService)
    {
        $this->liveHomeService = $liveHomeService;
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
     * 获取直播首页数据（预告/回放）。
     *
     * 流程：
     * 1. 由 LiveHomeRequest 负责参数验证和默认值处理；
     * 2. 控制器仅负责透传参数给服务层并包装资源响应；
     * 3. 异常场景记录必要上下文，统一返回通用错误，避免暴露内部细节。
     */
    public function home(LiveHomeRequest $request)
    {
        $tab = $request->getTab();
        $page = $request->getPage();
        $pageSize = $request->getPageSize();
        $memberId = $this->getMemberId($request);

        try {
            $result = $this->liveHomeService->getHomeData($tab, $page, $pageSize, $memberId);

            return AppApiResponse::success([
                'data' => (new LiveHomeResource($result))->resolve(),
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

}
