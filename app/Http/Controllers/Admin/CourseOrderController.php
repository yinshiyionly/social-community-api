<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CourseOrderListRequest;
use App\Http\Requests\Admin\CourseRefundListRequest;
use App\Http\Resources\Admin\CourseOrderListResource;
use App\Http\Resources\Admin\CourseRefundListResource;
use App\Http\Resources\ApiResponse;
use App\Services\Admin\CourseOrderService;
use Illuminate\Support\Facades\Log;

class CourseOrderController extends Controller
{
    /**
     * @var CourseOrderService
     */
    protected $courseOrderService;

    public function __construct(CourseOrderService $courseOrderService)
    {
        $this->courseOrderService = $courseOrderService;
    }

    /**
     * 课程订单列表
     */
    public function orderList(CourseOrderListRequest $request)
    {
        $filters = [
            'orderNo' => $request->input('orderNo'),
            'memberId' => $request->input('memberId'),
            'memberPhone' => $request->input('memberPhone'),
            'memberNickname' => $request->input('memberNickname'),
            'courseId' => $request->input('courseId'),
            'courseTitle' => $request->input('courseTitle'),
            'payType' => $request->input('payType'),
            'payStatus' => $request->input('payStatus'),
            'beginTime' => $request->input('beginTime'),
            'endTime' => $request->input('endTime'),
        ];

        $pageNum = (int)$request->input('pageNum', 1);
        $pageSize = (int)$request->input('pageSize', 10);

        try {
            $paginator = $this->courseOrderService->getOrderList($filters, $pageNum, $pageSize);

            return ApiResponse::paginate($paginator, CourseOrderListResource::class, '查询成功');
        } catch (\Exception $e) {
            Log::error('查询课程订单列表失败', [
                'action' => 'orderList',
                'filters' => $filters,
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 课程退款列表
     */
    public function refundList(CourseRefundListRequest $request)
    {
        $filters = [
            'orderNo' => $request->input('orderNo'),
            'memberId' => $request->input('memberId'),
            'memberPhone' => $request->input('memberPhone'),
            'memberNickname' => $request->input('memberNickname'),
            'courseId' => $request->input('courseId'),
            'courseTitle' => $request->input('courseTitle'),
            'payType' => $request->input('payType'),
            'refundStatus' => $request->input('refundStatus'),
            'beginTime' => $request->input('beginTime'),
            'endTime' => $request->input('endTime'),
        ];

        $pageNum = (int)$request->input('pageNum', 1);
        $pageSize = (int)$request->input('pageSize', 10);

        try {
            $paginator = $this->courseOrderService->getRefundList($filters, $pageNum, $pageSize);

            return ApiResponse::paginate($paginator, CourseRefundListResource::class, '查询成功');
        } catch (\Exception $e) {
            Log::error('查询课程退款列表失败', [
                'action' => 'refundList',
                'filters' => $filters,
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error('操作失败，请稍后重试');
        }
    }
}

