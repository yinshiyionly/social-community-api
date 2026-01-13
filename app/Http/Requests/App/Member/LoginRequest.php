<?php

namespace App\Http\Requests\App\Member;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'phone' => 'required|string|max:20',
            'password' => 'required|string|max:100',
        ];
    }

    public function messages()
    {
        return [
            'phone.required' => '手机号不能为空',
            'password.required' => '密码不能为空',
        ];
    }
}
