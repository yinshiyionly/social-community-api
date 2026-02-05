<?php

namespace App\Http\Requests\App;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 课程报名请求验证
 */
class CourseEnrollRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'courseId' => 'required|integer|min:1',
            'phone' => 'required|string|regex:/^1[3-9]\d{9}$/',
            'ageRange' => 'required|string|in:26-30岁,31-40岁,41-45岁,46-50岁,51-60岁,60岁+',
        ];
    }

    public function messages()
    {
        return [
            'courseId.required' => '请选择课程',
            'courseId.integer' => '课程ID格式错误',
            'phone.required' => '请输入手机号',
            'phone.regex' => '手机号格式不正确',
            'ageRange.required' => '请选择年龄段',
            'ageRange.in' => '年龄段选择无效',
        ];
    }
}
