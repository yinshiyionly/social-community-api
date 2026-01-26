<?php

namespace App\Http\Requests\App\Member;

use Illuminate\Foundation\Http\FormRequest;

class SmsLoginRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'phone' => 'required|string|regex:/^1[3-9]\d{9}$/',
            'code' => 'required|string|size:4',
        ];
    }

    public function messages()
    {
        return [
            'phone.required' => '手机号不能为空',
            'phone.regex' => '手机号格式不正确',
            'code.required' => '验证码不能为空',
            'code.size' => '验证码格式不正确',
        ];
    }
}
