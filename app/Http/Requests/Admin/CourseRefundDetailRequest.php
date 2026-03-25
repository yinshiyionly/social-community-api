<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 课程退款详情请求验证。
 */
class CourseRefundDetailRequest extends FormRequest
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
        ];
    }
}
