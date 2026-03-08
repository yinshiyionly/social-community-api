<?php

namespace App\Http\Requests\App;

use Illuminate\Foundation\Http\FormRequest;

class CourseOrderRefundRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'orderNo' => 'required|string|max:64',
            'reason' => 'nullable|string|max:200',
        ];
    }

    public function messages()
    {
        return [
            'orderNo.required' => '订单号不能为空',
            'orderNo.max' => '订单号长度不能超过64位',
            'reason.max' => '退款原因长度不能超过200位',
        ];
    }
}
