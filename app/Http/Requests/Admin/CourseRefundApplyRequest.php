<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 后台发起课程退款申请请求验证。
 *
 * 规则说明：
 * 1. 后台代客户发起申请时仅需要订单号与退款原因；
 * 2. 具体退款模式与金额由后续审核流程决定；
 * 3. 退款原因长度限制与 App 端保持一致，便于统一风控与展示。
 */
class CourseRefundApplyRequest extends FormRequest
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
            'reason' => 'required|string|max:200',
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
            'reason.required' => '退款原因不能为空',
            'reason.max' => '退款原因长度不能超过200位',
        ];
    }
}
