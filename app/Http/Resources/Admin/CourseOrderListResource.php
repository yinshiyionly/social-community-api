<?php

namespace App\Http\Resources\Admin;

use App\Models\App\AppCourseOrder;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 课程订单列表项资源。
 *
 * 字段约定：
 * 1. 同时返回支付状态与退款流程状态，便于后台列表直接展示“审核/执行进度”；
 * 2. 时间字段统一格式为 Y-m-d H:i:s，空值返回 null；
 * 3. 退款模式与审核状态均返回 code + text，前端无需重复维护映射表。
 */
class CourseOrderListResource extends JsonResource
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $payType = $this->pay_type !== null ? (int)$this->pay_type : null;
        $payStatus = $this->pay_status !== null ? (int)$this->pay_status : null;
        $refundStatus = $this->refund_status !== null ? (int)$this->refund_status : null;
        $refundReviewStatus = $refundStatus === AppCourseOrder::REFUND_STATUS_NONE
            ? null
            : ($this->refund_review_status !== null ? (int)$this->refund_review_status : null);
        $refundMode = $refundStatus === AppCourseOrder::REFUND_STATUS_NONE
            ? null
            : ($this->refund_mode !== null ? (int)$this->refund_mode : null);

        return [
            'orderId' => (int)$this->order_id,
            'orderNo' => $this->order_no,
            'memberId' => $this->member_id !== null ? (int)$this->member_id : null,
            'memberPhone' => $this->member_phone,
            'memberNickname' => $this->member_nickname,
            'courseId' => $this->course_id !== null ? (int)$this->course_id : null,
            'courseTitle' => $this->course_title,
            'courseCover' => $this->course_cover,
            'enrollPhone' => $this->enroll_phone,
            'enrollAgeRange' => $this->enroll_age_range,
            'paidAmount' => number_format((float)$this->paid_amount, 2, '.', ''),
            'payType' => $payType,
            'payTypeText' => $this->formatPayTypeText($payType),
            'payStatus' => $payStatus,
            'payStatusText' => $this->formatPayStatusText($payStatus),
            'payTradeNo' => $this->pay_trade_no,
            'payTime' => $this->formatDateTime($this->pay_time),
            'expireTime' => $this->formatDateTime($this->expire_time),
            'refundStatus' => $refundStatus,
            'refundStatusText' => $this->formatRefundStatusText($refundStatus),
            'refundReviewStatus' => $refundReviewStatus,
            'refundReviewStatusText' => $refundReviewStatus === null ? '' : $this->formatRefundReviewStatusText($refundReviewStatus),
            'refundMode' => $refundMode,
            'refundModeText' => $this->formatRefundModeText($refundMode),
            'refundAmount' => number_format((float)$this->refund_amount, 2, '.', ''),
            'refundReason' => $this->refund_reason,
            'refundApplyTime' => $this->formatDateTime($this->refund_apply_time),
            'refundReviewTime' => $this->formatDateTime($this->refund_review_time),
            'refundRejectReason' => $this->refund_reject_reason,
            'refundExecuteFailReason' => $this->refund_execute_fail_reason,
            'refundTime' => $this->formatDateTime($this->refund_time),
            'createdAt' => $this->formatDateTime($this->created_at),
        ];
    }

    /**
     * @param mixed $value
     * @return string|null
     */
    protected function formatDateTime($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        $timestamp = strtotime((string)$value);

        return $timestamp === false ? (string)$value : date('Y-m-d H:i:s', $timestamp);
    }

    /**
     * @param int|null $payType
     * @return string
     */
    protected function formatPayTypeText(?int $payType): string
    {
        $map = [
            AppCourseOrder::PAY_TYPE_WECHAT => '微信',
            AppCourseOrder::PAY_TYPE_ALIPAY => '支付宝',
            AppCourseOrder::PAY_TYPE_BALANCE => '余额',
            AppCourseOrder::PAY_TYPE_FREE => '免费',
        ];

        return $map[$payType] ?? '未知';
    }

    /**
     * @param int|null $payStatus
     * @return string
     */
    protected function formatPayStatusText(?int $payStatus): string
    {
        $map = [
            AppCourseOrder::PAY_STATUS_PENDING => '待支付',
            AppCourseOrder::PAY_STATUS_PAID => '已支付',
            AppCourseOrder::PAY_STATUS_REFUNDED => '已退款',
            AppCourseOrder::PAY_STATUS_CLOSED => '已关闭',
        ];

        return $map[$payStatus] ?? '未知';
    }

    /**
     * @param int|null $refundStatus
     * @return string
     */
    protected function formatRefundStatusText(?int $refundStatus): string
    {
        $map = [
            AppCourseOrder::REFUND_STATUS_NONE => '无',
            AppCourseOrder::REFUND_STATUS_APPLYING => '申请中',
            AppCourseOrder::REFUND_STATUS_REFUNDED => '已退款',
            AppCourseOrder::REFUND_STATUS_REJECTED => '已拒绝',
        ];

        return $map[$refundStatus] ?? '未知';
    }

    /**
     * @param int|null $refundReviewStatus
     * @return string
     */
    protected function formatRefundReviewStatusText(?int $refundReviewStatus): string
    {
        $map = [
            AppCourseOrder::REFUND_REVIEW_STATUS_PENDING => '待审核',
            AppCourseOrder::REFUND_REVIEW_STATUS_APPROVED => '已通过待执行',
            AppCourseOrder::REFUND_REVIEW_STATUS_REJECTED => '已拒绝',
        ];

        return $map[$refundReviewStatus] ?? '未知';
    }

    /**
     * @param int|null $refundMode
     * @return string
     */
    protected function formatRefundModeText(?int $refundMode): string
    {
        if ($refundMode === null) {
            return '';
        }

        $map = [
            AppCourseOrder::REFUND_MODE_FULL => '全额退款',
            AppCourseOrder::REFUND_MODE_PARTIAL => '部分退款',
        ];

        return $map[$refundMode] ?? '未知';
    }
}
