<?php

namespace App\Services\Admin;

use App\Models\App\AppCourseOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class CourseOrderService
{
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

        if (!empty($filters['beginTime'])) {
            $query->where('app_course_order.refund_time', '>=', $filters['beginTime']);
        }
        if (!empty($filters['endTime'])) {
            $query->where('app_course_order.refund_time', '<=', $filters['endTime']);
        }

        $query->orderByRaw('app_course_order.refund_time DESC NULLS LAST')
            ->orderByDesc('app_course_order.order_id');

        return $query->paginate($pageSize, ['*'], 'pageNum', $pageNum);
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
                'app_course_order.refund_amount',
                'app_course_order.refund_reason',
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
}

