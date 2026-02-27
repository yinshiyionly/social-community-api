<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\App\ScheduleDateRequest;
use App\Http\Requests\App\ScheduleRangeRequest;
use App\Http\Requests\App\ScheduleWeekRequest;
use App\Http\Resources\App\AppApiResponse;
use App\Http\Resources\App\MemberCourseListResource;
use App\Http\Resources\App\ScheduleDailyResource;
use App\Services\App\LearningCenterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LearningCenterController extends Controller
{
    /**
     * @var LearningCenterService
     */
    protected $learningCenterService;

    public function __construct(LearningCenterService $service)
    {
        $this->learningCenterService = $service;
    }

    /**
     * 我的课程列表
     */
    public function myCourses(Request $request)
    {
        $memberId = $request->attributes->get('member_id');

        try {
            $courses = $this->learningCenterService->getMyCourses($memberId);

            return AppApiResponse::collection($courses, MemberCourseListResource::class);
        } catch (\Exception $e) {
            Log::error('获取我的课程列表失败', [
                'member_id' => $memberId,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 课表日视图
     */
    public function dailySchedule(ScheduleDateRequest $request)
    {
        $memberId = $request->attributes->get('member_id');
        $date = $request->input('date');

        try {
            $schedules = $this->learningCenterService->getDailySchedule($memberId, $date);

            return AppApiResponse::collection($schedules, ScheduleDailyResource::class);
        } catch (\Exception $e) {
            Log::error('获取课表日视图失败', [
                'member_id' => $memberId,
                'date' => $date,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 课表周概览
     */
    public function weekOverview(ScheduleWeekRequest $request)
    {
        $memberId = $request->attributes->get('member_id');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');

        try {
            $overview = $this->learningCenterService->getWeekOverview($memberId, $startDate, $endDate);

            return AppApiResponse::success(['data' => $overview]);
        } catch (\Exception $e) {
            Log::error('获取课表周概览失败', [
                'member_id' => $memberId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 课表区间数据（日期分组 + 日历红点）
     */
    public function scheduleRange(ScheduleRangeRequest $request)
    {
        $memberId = $request->attributes->get('member_id');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');

        try {
            $data = $this->learningCenterService->getScheduleRange($memberId, $startDate, $endDate);

            return AppApiResponse::success(['data' => $data]);
        } catch (\Exception $e) {
            Log::error('获取课表区间数据失败', [
                'member_id' => $memberId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

}
