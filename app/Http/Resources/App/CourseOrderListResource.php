<?php

namespace App\Http\Resources\App;

use App\Models\App\AppCourseOrder;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * App 端我的订单列表项资源。
 *
 * 字段约定：
 * 1. orderId 使用业务订单号 order_no，避免与自增主键 order_id 混淆；
 * 2. amount 输出 number，保持金额精度语义且便于前端直接数值处理；
 * 3. createTime 固定格式 YYYY.MM.DD HH:mm，统一前端展示口径。
 */
class CourseOrderListResource extends JsonResource
{
    /**
     * 输出我的订单列表项。
     *
     * @param \Illuminate\Http\Request $request
     * @return array{orderId:string,title:string,amount:float,status:string,createTime:string,courseId:int}
     */
    public function toArray($request)
    {
        return [
            'orderId' => (string)$this->order_no,
            'title' => (string)$this->course_title,
            'amount' => $this->formatAmount($this->paid_amount),
            'status' => $this->formatOrderStatus((int)$this->pay_status),
            'createTime' => $this->formatCreateTime($this->created_at),
            'courseId' => (int)$this->course_id,
        ];
    }

    /**
     * 格式化金额为 number 类型，两位小数语义。
     *
     * @param mixed $amount
     * @return float
     */
    protected function formatAmount($amount): float
    {
        return (float)number_format((float)$amount, 2, '.', '');
    }

    /**
     * 将支付状态映射为前端约定的订单状态字符串。
     *
     * @param int $payStatus
     * @return string
     */
    protected function formatOrderStatus(int $payStatus): string
    {
        $map = [
            AppCourseOrder::PAY_STATUS_PENDING => 'unpaid',
            AppCourseOrder::PAY_STATUS_PAID => 'paid',
            AppCourseOrder::PAY_STATUS_CLOSED => 'closed',
            AppCourseOrder::PAY_STATUS_REFUNDED => 'refunded',
        ];

        return $map[$payStatus] ?? 'closed';
    }

    /**
     * 格式化下单时间。
     *
     * @param mixed $value
     * @return string
     */
    protected function formatCreateTime($value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y.m.d H:i');
        }

        $timestamp = strtotime((string)$value);

        if ($timestamp === false) {
            return '';
        }

        return date('Y.m.d H:i', $timestamp);
    }
}
