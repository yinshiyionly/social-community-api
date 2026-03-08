<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CourseRefundListRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'pageNum' => 'nullable|integer|min:1',
            'pageSize' => 'nullable|integer|min:1|max:100',
            'orderNo' => 'nullable|string|max:64',
            'memberId' => 'nullable|integer|min:1',
            'memberPhone' => 'nullable|string|max:20',
            'memberNickname' => 'nullable|string|max:100',
            'courseId' => 'nullable|integer|min:1',
            'courseTitle' => 'nullable|string|max:200',
            'payType' => 'nullable|integer|in:1,2,3,4',
            'refundStatus' => 'nullable|integer|in:1,2,3',
            'beginTime' => 'nullable|date',
            'endTime' => 'nullable|date',
        ];
    }

    public function messages()
    {
        return [
            'pageNum.integer' => '页码必须是整数',
            'pageNum.min' => '页码不能小于1',
            'pageSize.integer' => '每页条数必须是整数',
            'pageSize.min' => '每页条数不能小于1',
            'pageSize.max' => '每页条数不能超过100',
            'orderNo.max' => '订单号长度不能超过64位',
            'memberId.integer' => '用户ID必须是整数',
            'memberId.min' => '用户ID无效',
            'memberPhone.max' => '手机号长度不能超过20位',
            'memberNickname.max' => '用户昵称长度不能超过100位',
            'courseId.integer' => '课程ID必须是整数',
            'courseId.min' => '课程ID无效',
            'courseTitle.max' => '课程标题长度不能超过200位',
            'payType.in' => '支付方式值无效',
            'refundStatus.in' => '退款状态值无效',
            'beginTime.date' => '开始时间格式无效',
            'endTime.date' => '结束时间格式无效',
        ];
    }
}

