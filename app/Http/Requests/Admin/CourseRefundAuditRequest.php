<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 课程退款审核请求验证。
 */
class CourseRefundAuditRequest extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    public function rules()
    {
        return [
            'orderNo' => 'required|string|max:64',
            'auditStatus' => 'required|integer|in:1,2',
            'refundMode' => 'required_if:auditStatus,1|integer|in:1,2',
            'refundAmount' => 'required_if:auditStatus,1|numeric|min:0.01',
            'rejectReason' => 'required_if:auditStatus,2|string|max:200',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'orderNo.required' => '订单号不能为空',
            'orderNo.max' => '订单号长度不能超过64位',
            'auditStatus.required' => '审核状态不能为空',
            'auditStatus.integer' => '审核状态必须为整数',
            'auditStatus.in' => '审核状态值无效',
            'refundMode.required_if' => '审核通过时必须选择退款模式',
            'refundMode.integer' => '退款模式必须为整数',
            'refundMode.in' => '退款模式值无效',
            'refundAmount.required_if' => '审核通过时必须填写退款金额',
            'refundAmount.numeric' => '退款金额格式错误',
            'refundAmount.min' => '退款金额必须大于0',
            'rejectReason.required_if' => '审核拒绝时必须填写拒绝原因',
            'rejectReason.max' => '拒绝原因长度不能超过200位',
        ];
    }
}
