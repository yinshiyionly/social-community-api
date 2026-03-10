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
            'id' => 'required|integer|min:1',
            'phone' => 'required|string|regex:/^1[3-9]\d{9}$/',
            'age' => 'required|string|in:26-30,31-40,41-45,46-50,51-60,60+'
        ];
    }

    public function messages()
    {
        return [
            'id.required' => '请选择课程',
            'id.integer' => '课程ID格式错误',
            'phone.required' => '请输入手机号',
            'phone.regex' => '手机号格式不正确',
            'age.required' => '请选择年龄段',
            'age.in' => '年龄段选择无效',
        ];
    }
}
