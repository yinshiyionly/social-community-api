<?php

namespace App\Http\Requests\App;

use Illuminate\Foundation\Http\FormRequest;

class CourseOrderStatusRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'orderNo' => 'required|string|max:64',
        ];
    }

    public function messages()
    {
        return [
            'orderNo.required' => '订单号不能为空',
            'orderNo.max' => '订单号长度不能超过64位',
        ];
    }
}
