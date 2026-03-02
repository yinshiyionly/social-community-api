<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\App\StudyCourseListRequest;
use App\Http\Resources\App\AppApiResponse;
use App\Services\App\StudyCourseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * 学习页（课程Tab）控制器
 */
class StudyCourseController extends Controller
{
    /**
     * @var StudyCourseService
     */
    protected $studyCourseService;

    public function __construct(StudyCourseService $service)
    {
        $this->studyCourseService = $service;
    }

    /**
     * 获取课程分类筛选项
     */
    public function filters()
    {
        try {
            $data = $this->studyCourseService->getCourseFilters();

            return AppApiResponse::success(['data' => $data]);
        } catch (\Exception $e) {
            Log::error('获取课程分类筛选项失败', [
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 获取课程付费类型筛选项
     */
    public function allTypes()
    {
        try {
            $data = $this->studyCourseService->getCoursePayTypes();

            return AppApiResponse::success(['data' => $data]);
        } catch (\Exception $e) {
            Log::error('获取课程付费类型筛选项失败', [
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 获取学习页总览数据
     */
    public function overview(Request $request)
    {
        $memberId = $request->attributes->get('member_id');

        try {
            $data = $this->studyCourseService->getOverview($memberId);

            return AppApiResponse::success(['data' => $data]);
        } catch (\Exception $e) {
            Log::error('获取学习页总览数据失败', [
                'member_id' => $memberId,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 获取筛选后的课程列表
     */
    public function list(StudyCourseListRequest $request)
    {
        $memberId = $request->attributes->get('member_id');
        $categoryId = $request->input('filter');
        $payType = $request->input('filterType');
        $page = (int) $request->input('page', 1);
        $pageSize = (int) $request->input('pageSize', 10);

        try {
            $data = $this->studyCourseService->getFilteredCourseList(
                $memberId,
                $categoryId,
                $payType,
                $page,
                $pageSize
            );

            return AppApiResponse::success(['data' => $data]);
        } catch (\Exception $e) {
            Log::error('获取学习页课程列表失败', [
                'member_id' => $memberId,
                'category_id' => $categoryId,
                'pay_type' => $payType,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

}
