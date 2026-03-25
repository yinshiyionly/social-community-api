<?php

namespace App\Services\Admin;

use App\Models\App\AppCourseOrder;
use App\Models\System\SystemUser;
use App\Services\App\CourseOrderService as AppCourseOrderService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * 后台课程订单与退款服务。
 *
 * 职责：
 * 1. 提供课程订单/退款列表与详情查询能力；
 * 2. 处理退款审核（通过/拒绝）状态流转；
 * 3. 协调调用 App 侧订单服务执行微信退款并回写执行结果。
 */
class CourseOrderService
{
    /**
     * 审核结果：通过。
     */
    const AUDIT_STATUS_APPROVED = 1;

    /**
     * 审核结果：拒绝。
     */
    const AUDIT_STATUS_REJECTED = 2;

    /**
     * @var AppCourseOrderService
     */
    protected $appCourseOrderService;

    public function __construct(AppCourseOrderService $appCourseOrderService)
    {
        $this->appCourseOrderService = $appCourseOrderService;
    }

    /**
     * 订单列表
     */
    public function getOrderList(array $filters, int $pageNum = 1, int $pageSize = 10): LengthAwarePaginator
    {
        $query = $this->buildBaseQuery($filters);

        if (isset($filters['payStatus']) && $filters['payStatus'] !== '') {
            $query->where('app_course_order.pay_status', (int)$filters['payStatus']);
        }

        if (!empty($filters['beginTime'])) {
            $query->where('app_course_order.created_at', '>=', $filters['beginTime']);
        }
        if (!empty($filters['endTime'])) {
            $query->where('app_course_order.created_at', '<=', $filters['endTime']);
        }

        $query->orderByDesc('app_course_order.order_id');

        return $query->paginate($pageSize, ['*'], 'pageNum', $pageNum);
    }

    /**
     * 退款列表
     */
    public function getRefundList(array $filters, int $pageNum = 1, int $pageSize = 10): LengthAwarePaginator
    {
        $query = $this->buildBaseQuery($filters);

        if (isset($filters['refundStatus']) && $filters['refundStatus'] !== '') {
            $query->where('app_course_order.refund_status', (int)$filters['refundStatus']);
        } else {
            $query->where('app_course_order.refund_status', '<>', AppCourseOrder::REFUND_STATUS_NONE);
        }

        if (isset($filters['refundReviewStatus']) && $filters['refundReviewStatus'] !== '') {
            $query->where('app_course_order.refund_review_status', (int)$filters['refundReviewStatus']);
        }

        if (isset($filters['refundMode']) && $filters['refundMode'] !== '') {
            $query->where('app_course_order.refund_mode', (int)$filters['refundMode']);
        }

        if (!empty($filters['beginTime'])) {
            $query->whereRaw(
                'COALESCE(app_course_order.refund_apply_time, app_course_order.refund_time) >= ?',
                [$filters['beginTime']]
            );
        }
        if (!empty($filters['endTime'])) {
            $query->whereRaw(
                'COALESCE(app_course_order.refund_apply_time, app_course_order.refund_time) <= ?',
                [$filters['endTime']]
            );
        }

        $query->orderByRaw('COALESCE(app_course_order.refund_apply_time, app_course_order.refund_time) DESC NULLS LAST')
            ->orderByDesc('app_course_order.order_id');

        return $query->paginate($pageSize, ['*'], 'pageNum', $pageNum);
    }

    /**
     * 获取课程订单详情。
     *
     * @param string $orderNo
     * @return object|null
     */
    public function getOrderDetail(string $orderNo)
    {
        return $this->buildDetailQuery()
            ->where('app_course_order.order_no', $orderNo)
            ->first();
    }

    /**
     * 获取课程退款详情。
     *
     * @param string $orderNo
     * @return object|null
     */
    public function getRefundDetail(string $orderNo)
    {
        return $this->buildDetailQuery()
            ->where('app_course_order.order_no', $orderNo)
            ->first();
    }

    /**
     * 审核退款申请（通过/拒绝）。
     *
     * 关键规则：
     * 1. 仅“申请中+待审核”订单允许审核；
     * 2. 审核通过时必须确定退款模式，部分退款需填写金额；
     * 3. 审核拒绝后允许用户重新发起新申请。
     *
     * @param string $orderNo
     * @param int $auditStatus
     * @param int|null $refundMode
     * @param float|null $refundAmount
     * @param string|null $rejectReason
     * @param SystemUser|null $operator
     * @return array<string, mixed>
     * @throws \Exception
     */
    public function auditRefund(
        string $orderNo,
        int $auditStatus,
        ?int $refundMode = null,
        ?float $refundAmount = null,
        ?string $rejectReason = null,
        ?SystemUser $operator = null
    ): array {
        return DB::transaction(function () use ($orderNo, $auditStatus, $refundMode, $refundAmount, $rejectReason, $operator) {
            $order = AppCourseOrder::query()
                ->where('order_no', $orderNo)
                ->lockForUpdate()
                ->first();

            if (!$order) {
                throw new \Exception('订单不存在');
            }

            if ((int)$order->pay_type !== AppCourseOrder::PAY_TYPE_WECHAT) {
                throw new \Exception('仅支持微信支付订单退款');
            }

            if ((int)$order->pay_status !== AppCourseOrder::PAY_STATUS_PAID) {
                throw new \Exception('当前订单状态不支持审核退款');
            }

            if ((int)$order->refund_status === AppCourseOrder::REFUND_STATUS_REFUNDED) {
                throw new \Exception('当前订单已完成退款');
            }

            // 仅待审核申请允许进入审核流，避免覆盖历史审核/执行结果。
            if (
                (int)$order->refund_status !== AppCourseOrder::REFUND_STATUS_APPLYING
                || (int)$order->refund_review_status !== AppCourseOrder::REFUND_REVIEW_STATUS_PENDING
            ) {
                throw new \Exception('当前退款申请状态不支持审核');
            }

            $operatorId = $operator ? (int)$operator->user_id : null;
            $now = now();

            if ($auditStatus === self::AUDIT_STATUS_APPROVED) {
                if (!in_array((int)$refundMode, [AppCourseOrder::REFUND_MODE_FULL, AppCourseOrder::REFUND_MODE_PARTIAL], true)) {
                    throw new \Exception('退款模式无效');
                }

                $paidAmount = round((float)$order->paid_amount, 2);
                $approvedAmount = 0.0;

                if ((int)$refundMode === AppCourseOrder::REFUND_MODE_FULL) {
                    $approvedAmount = $paidAmount;
                } else {
                    $approvedAmount = $this->normalizeAmount($refundAmount);
                    if ($approvedAmount <= 0 || $approvedAmount >= $paidAmount) {
                        throw new \Exception('部分退款金额必须大于0且小于实付金额');
                    }
                }

                $order->refund_status = AppCourseOrder::REFUND_STATUS_APPLYING;
                $order->refund_review_status = AppCourseOrder::REFUND_REVIEW_STATUS_APPROVED;
                $order->refund_mode = (int)$refundMode;
                $order->refund_amount = $approvedAmount;
                $order->refund_review_by = $operatorId;
                $order->refund_review_time = $now;
                $order->refund_reject_reason = null;
                $order->refund_execute_fail_reason = null;
                $order->save();

                return $this->buildRefundFlowResult($order);
            }

            if ($auditStatus !== self::AUDIT_STATUS_REJECTED) {
                throw new \Exception('审核状态值无效');
            }

            $rejectReason = trim((string)$rejectReason);
            if ($rejectReason === '') {
                throw new \Exception('审核拒绝时必须填写拒绝原因');
            }

            $order->refund_status = AppCourseOrder::REFUND_STATUS_REJECTED;
            $order->refund_review_status = AppCourseOrder::REFUND_REVIEW_STATUS_REJECTED;
            $order->refund_mode = null;
            $order->refund_amount = 0;
            $order->refund_review_by = $operatorId;
            $order->refund_review_time = $now;
            $order->refund_reject_reason = $rejectReason;
            $order->refund_execute_by = null;
            $order->refund_execute_fail_reason = null;
            $order->refund_time = null;
            $order->save();

            return $this->buildRefundFlowResult($order);
        });
    }

    /**
     * 执行退款申请。
     *
     * @param string $orderNo
     * @param SystemUser|null $operator
     * @param string|null $clientIp
     * @return array<string, mixed>
     * @throws \Exception
     */
    public function executeRefund(string $orderNo, ?SystemUser $operator = null, ?string $clientIp = null): array
    {
        $operatorId = $operator ? (int)$operator->user_id : null;

        return $this->appCourseOrderService->executeApprovedRefundByAdmin($orderNo, $operatorId, $clientIp);
    }

    /**
     * 后台代客户发起退款申请。
     *
     * 关键规则：
     * 1. 后台发起仅负责受理申请，不在此处执行微信退款；
     * 2. 申请规则与 App 端保持一致，统一复用 App 订单服务的申请逻辑；
     * 3. 订单不存在时快速失败，避免把错误上下文传入下游流程。
     *
     * @param string $orderNo
     * @param string $reason
     * @param string|null $clientIp
     * @return array<string, mixed>
     * @throws \Exception
     */
    public function applyRefundByAdmin(string $orderNo, string $reason, ?string $clientIp = null): array
    {
        $order = AppCourseOrder::query()
            ->select(['order_no', 'member_id'])
            ->where('order_no', $orderNo)
            ->first();

        if (!$order) {
            throw new \Exception('订单不存在');
        }

        return $this->appCourseOrderService->applyRefund(
            (int)$order->member_id,
            (string)$order->order_no,
            $reason,
            $clientIp
        );
    }

    /**
     * 构建基础查询
     */
    protected function buildBaseQuery(array $filters): Builder
    {
        $query = AppCourseOrder::query()
            ->leftJoin('app_member_base as mb', 'app_course_order.member_id', '=', 'mb.member_id')
            ->select([
                'app_course_order.order_id',
                'app_course_order.order_no',
                'app_course_order.member_id',
                'app_course_order.course_id',
                'app_course_order.course_title',
                'app_course_order.course_cover',
                'app_course_order.enroll_phone',
                'app_course_order.enroll_age_range',
                'app_course_order.paid_amount',
                'app_course_order.pay_type',
                'app_course_order.pay_status',
                'app_course_order.pay_trade_no',
                'app_course_order.pay_time',
                'app_course_order.expire_time',
                'app_course_order.refund_status',
                'app_course_order.refund_review_status',
                'app_course_order.refund_mode',
                'app_course_order.refund_amount',
                'app_course_order.refund_reason',
                'app_course_order.refund_apply_time',
                'app_course_order.refund_review_time',
                'app_course_order.refund_reject_reason',
                'app_course_order.refund_execute_fail_reason',
                'app_course_order.refund_time',
                'app_course_order.created_at',
                'mb.phone as member_phone',
                'mb.nickname as member_nickname',
            ]);

        $this->applyCommonFilters($query, $filters);

        return $query;
    }

    /**
     * 通用筛选条件
     */
    protected function applyCommonFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['orderNo'])) {
            $query->where('app_course_order.order_no', $filters['orderNo']);
        }

        if (!empty($filters['memberId'])) {
            $query->where('app_course_order.member_id', (int)$filters['memberId']);
        }

        if (!empty($filters['memberPhone'])) {
            $query->where('mb.phone', $filters['memberPhone']);
        }

        if (!empty($filters['memberNickname'])) {
            $query->where('mb.nickname', 'like', '%' . $filters['memberNickname'] . '%');
        }

        if (!empty($filters['courseId'])) {
            $query->where('app_course_order.course_id', (int)$filters['courseId']);
        }

        if (!empty($filters['courseTitle'])) {
            $query->where('app_course_order.course_title', 'like', '%' . $filters['courseTitle'] . '%');
        }

        if (isset($filters['payType']) && $filters['payType'] !== '') {
            $query->where('app_course_order.pay_type', (int)$filters['payType']);
        }
    }

    /**
     * 构建订单/退款详情查询。
     *
     * @return Builder
     */
    protected function buildDetailQuery(): Builder
    {
        return AppCourseOrder::query()
            ->leftJoin('app_member_base as mb', 'app_course_order.member_id', '=', 'mb.member_id')
            ->leftJoin('sys_user as review_user', 'app_course_order.refund_review_by', '=', 'review_user.user_id')
            ->leftJoin('sys_user as execute_user', 'app_course_order.refund_execute_by', '=', 'execute_user.user_id')
            ->select([
                'app_course_order.order_id',
                'app_course_order.order_no',
                'app_course_order.member_id',
                'app_course_order.course_id',
                'app_course_order.course_title',
                'app_course_order.course_cover',
                'app_course_order.enroll_phone',
                'app_course_order.enroll_age_range',
                'app_course_order.paid_amount',
                'app_course_order.pay_type',
                'app_course_order.pay_status',
                'app_course_order.pay_trade_no',
                'app_course_order.pay_time',
                'app_course_order.expire_time',
                'app_course_order.refund_status',
                'app_course_order.refund_review_status',
                'app_course_order.refund_mode',
                'app_course_order.refund_amount',
                'app_course_order.refund_reason',
                'app_course_order.refund_apply_time',
                'app_course_order.refund_review_by',
                'app_course_order.refund_review_time',
                'app_course_order.refund_reject_reason',
                'app_course_order.refund_execute_by',
                'app_course_order.refund_execute_fail_reason',
                'app_course_order.refund_time',
                'app_course_order.created_at',
                'mb.phone as member_phone',
                'mb.nickname as member_nickname',
                DB::raw("COALESCE(review_user.nick_name, review_user.user_name) as refund_review_user_name"),
                DB::raw("COALESCE(execute_user.nick_name, execute_user.user_name) as refund_execute_user_name"),
            ]);
    }

    /**
     * 构建审核/执行接口返回数据。
     *
     * @param AppCourseOrder $order
     * @return array<string, mixed>
     */
    protected function buildRefundFlowResult(AppCourseOrder $order): array
    {
        return [
            'orderNo' => $order->order_no,
            'refundStatus' => (int)$order->refund_status,
            'refundReviewStatus' => (int)$order->refund_review_status,
            'refundMode' => $order->refund_mode !== null ? (int)$order->refund_mode : null,
            'refundAmount' => number_format((float)$order->refund_amount, 2, '.', ''),
            'refundReviewTime' => optional($order->refund_review_time)->format('Y-m-d H:i:s'),
            'refundRejectReason' => $order->refund_reject_reason,
            'refundExecuteFailReason' => $order->refund_execute_fail_reason,
            'refundTime' => optional($order->refund_time)->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * 标准化金额为两位小数。
     *
     * @param float|null $amount
     * @return float
     */
    protected function normalizeAmount(?float $amount): float
    {
        return round((float)$amount, 2);
    }
}
