<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class CourseOrderListResource extends JsonResource
{
    public function toArray($request)
    {
        $payType = $this->pay_type !== null ? (int)$this->pay_type : null;
        $payStatus = $this->pay_status !== null ? (int)$this->pay_status : null;
        $refundStatus = $this->refund_status !== null ? (int)$this->refund_status : null;

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
            'refundAmount' => number_format((float)$this->refund_amount, 2, '.', ''),
            'refundReason' => $this->refund_reason,
            'refundTime' => $this->formatDateTime($this->refund_time),
            'createdAt' => $this->formatDateTime($this->created_at),
        ];
    }

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

    protected function formatPayTypeText(?int $payType): string
    {
        $map = [
            1 => '微信',
            2 => '支付宝',
            3 => '余额',
            4 => '免费',
        ];

        return $map[$payType] ?? '未知';
    }

    protected function formatPayStatusText(?int $payStatus): string
    {
        $map = [
            0 => '待支付',
            1 => '已支付',
            2 => '已退款',
            3 => '已关闭',
        ];

        return $map[$payStatus] ?? '未知';
    }

    protected function formatRefundStatusText(?int $refundStatus): string
    {
        $map = [
            0 => '无',
            1 => '申请中',
            2 => '已退款',
            3 => '已拒绝',
        ];

        return $map[$refundStatus] ?? '未知';
    }
}

