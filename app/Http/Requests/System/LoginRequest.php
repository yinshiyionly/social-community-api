<?php

namespace App\Http\Requests\System;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'username' => 'required|string|max:20',
            'password' => 'required|string|max:30',
            'code' => 'required|string|max:10',
        ];
    }

    public function messages()
    {
        return [
            'username.required' => '用户名不能为空。',
            'username.string' => '用户名必须是字符串。',
            'username.max' => '用户名不能超过 20 个字符。',

            'password.required' => '密码不能为空。',
            'password.string' => '密码必须是字符串。',
            'password.max' => '密码不能超过 30 个字符。',

            'code.required' => '验证码不能为空。',
            'code.string' => '验证码必须是字符串。',
            'code.max' => '验证码不能超过 10 个字符。',
        ];
    }
}
