<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Resources\App\AppApiResponse;
use App\Services\App\CheckinService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckinController extends Controller
{
    /**
     * @var CheckinService
     */
    protected $checkinService;

    public function __construct(CheckinService $checkinService)
    {
        $this->checkinService = $checkinService;
    }

    /**
     * 获取当前登录会员ID
     *
     * @param Request $request
     * @return int|null
     */
    protected function getMemberId(Request $request)
    {
        return $request->attributes->get('member_id');
    }

    /**
     * 执行签到
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkin(Request $request): JsonResponse
    {
        $memberId = $this->getMemberId($request);
        $clientIp = $request->ip();
        $deviceInfo = $request->header('User-Agent');

        try {
            $result = $this->checkinService->checkin($memberId, $clientIp, $deviceInfo);

            if (!$result['success']) {
                return AppApiResponse::error($result['message']);
            }

            return AppApiResponse::success([
                'data' => $result['data'],
            ], $result['message']);
        } catch (\Exception $e) {
            Log::error('签到失败', [
                'member_id' => $memberId,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 获取签到状态
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function status(Request $request): JsonResponse
    {
        $memberId = $this->getMemberId($request);

        try {
            $status = $this->checkinService->getCheckinStatus($memberId);

            return AppApiResponse::success([
                'data' => $status,
            ]);
        } catch (\Exception $e) {
            Log::error('获取签到状态失败', [
                'member_id' => $memberId,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 获取签到奖励配置列表（7天奖励展示）
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function rewardList(Request $request): JsonResponse
    {
        $memberId = $this->getMemberId($request);

        try {
            $list = $this->checkinService->getRewardConfigList($memberId);

            return AppApiResponse::success([
                'data' => $list,
            ]);
        } catch (\Exception $e) {
            Log::error('获取签到奖励配置失败', [
                'member_id' => $memberId,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 获取月度签到记录（日历展示）
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function monthlyRecords(Request $request): JsonResponse
    {
        $memberId = $this->getMemberId($request);
        $year = (int)$request->input('year', date('Y'));
        $month = (int)$request->input('month', date('m'));

        // 参数校验
        if ($year < 2020 || $year > 2100) {
            return AppApiResponse::error('年份参数无效');
        }

        if ($month < 1 || $month > 12) {
            return AppApiResponse::error('月份参数无效');
        }

        try {
            $records = $this->checkinService->getMonthlyRecords($memberId, $year, $month);

            return AppApiResponse::success([
                'data' => $records,
            ]);
        } catch (\Exception $e) {
            Log::error('获取月度签到记录失败', [
                'member_id' => $memberId,
                'year' => $year,
                'month' => $month,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }
}
