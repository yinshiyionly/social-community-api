<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CourseOrderDetailRequest;
use App\Http\Requests\Admin\CourseOrderListRequest;
use App\Http\Requests\Admin\CourseRefundApplyRequest;
use App\Http\Requests\Admin\CourseRefundAuditRequest;
use App\Http\Requests\Admin\CourseRefundDetailRequest;
use App\Http\Requests\Admin\CourseRefundExecuteRequest;
use App\Http\Requests\Admin\CourseRefundListRequest;
use App\Http\Resources\Admin\CourseOrderDetailResource;
use App\Http\Resources\Admin\CourseOrderListResource;
use App\Http\Resources\Admin\CourseRefundDetailResource;
use App\Http\Resources\Admin\CourseRefundListResource;
use App\Http\Resources\ApiResponse;
use App\Services\Admin\CourseOrderService;
use Illuminate\Support\Facades\Log;

/**
 * 课程订单与退款后台控制器。
 *
 * 职责：
 * 1. 提供课程订单/退款列表与详情查询；
 * 2. 处理退款审核与退款执行操作；
 * 3. 统一记录异常日志并返回标准化后台响应结构。
 */
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
     * 课程订单列表。
     *
     * @param CourseOrderListRequest $request
     * @return \Illuminate\Http\JsonResponse
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
     * 课程退款列表。
     *
     * @param CourseRefundListRequest $request
     * @return \Illuminate\Http\JsonResponse
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
            'refundReviewStatus' => $request->input('refundReviewStatus'),
            'refundMode' => $request->input('refundMode'),
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

    /**
     * 课程订单详情。
     *
     * @param CourseOrderDetailRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function orderDetail(CourseOrderDetailRequest $request)
    {
        $orderNo = (string)$request->input('orderNo');

        try {
            $detail = $this->courseOrderService->getOrderDetail($orderNo);
            if (!$detail) {
                return ApiResponse::error('订单不存在');
            }

            return ApiResponse::resource($detail, CourseOrderDetailResource::class, '查询成功');
        } catch (\Exception $e) {
            Log::error('查询课程订单详情失败', [
                'action' => 'orderDetail',
                'order_no' => $orderNo,
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 课程退款详情。
     *
     * @param CourseRefundDetailRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function refundDetail(CourseRefundDetailRequest $request)
    {
        $orderNo = (string)$request->input('orderNo');

        try {
            $detail = $this->courseOrderService->getRefundDetail($orderNo);
            if (!$detail) {
                return ApiResponse::error('订单不存在');
            }

            return ApiResponse::resource($detail, CourseRefundDetailResource::class, '查询成功');
        } catch (\Exception $e) {
            Log::error('查询课程退款详情失败', [
                'action' => 'refundDetail',
                'order_no' => $orderNo,
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 审核退款申请（通过/拒绝）。
     *
     * @param CourseRefundAuditRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function auditRefund(CourseRefundAuditRequest $request)
    {
        $orderNo = (string)$request->input('orderNo');
        $auditStatus = (int)$request->input('auditStatus');
        $refundMode = $request->input('refundMode');
        $refundAmount = $request->input('refundAmount');
        $rejectReason = $request->input('rejectReason');

        try {
            $result = $this->courseOrderService->auditRefund(
                $orderNo,
                $auditStatus,
                $refundMode !== null ? (int)$refundMode : null,
                $refundAmount !== null ? (float)$refundAmount : null,
                $rejectReason !== null ? (string)$rejectReason : null,
                $request->user()
            );

            return ApiResponse::success([
                'data' => $result,
            ], '审核成功');
        } catch (\Exception $e) {
            Log::error('审核课程退款申请失败', [
                'action' => 'auditRefund',
                'order_no' => $orderNo,
                'audit_status' => $auditStatus,
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error($e->getMessage());
        }
    }

    /**
     * 执行课程退款（调用微信退款）。
     *
     * @param CourseRefundExecuteRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function executeRefund(CourseRefundExecuteRequest $request)
    {
        $orderNo = (string)$request->input('orderNo');

        try {
            $result = $this->courseOrderService->executeRefund(
                $orderNo,
                $request->user(),
                $request->ip()
            );

            return ApiResponse::success([
                'data' => $result,
            ], '执行成功');
        } catch (\Exception $e) {
            Log::error('执行课程退款失败', [
                'action' => 'executeRefund',
                'order_no' => $orderNo,
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error($e->getMessage());
        }
    }

    /**
     * 后台代客户发起退款申请。
     *
     * 关键输入：
     * - orderNo：订单号；
     * - reason：退款原因（文本）。
     *
     * 关键输出：
     * - 返回申请结果与当前退款流程状态字段，供订单列表弹窗提交后刷新状态。
     *
     * @param CourseRefundApplyRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function applyRefund(CourseRefundApplyRequest $request)
    {
        $orderNo = (string)$request->input('orderNo');
        $reason = (string)$request->input('reason');

        try {
            $result = $this->courseOrderService->applyRefundByAdmin($orderNo, $reason, $request->ip());

            return ApiResponse::success([
                'data' => $result,
            ], '退款申请已提交');
        } catch (\Exception $e) {
            Log::error('后台发起课程退款申请失败', [
                'action' => 'applyRefund',
                'order_no' => $orderNo,
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error($e->getMessage());
        }
    }
}
