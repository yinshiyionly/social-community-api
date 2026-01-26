<?php

namespace App\Http\Requests\App\Member;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 微信 APP 登录请求验证
 */
class WeChatLoginRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'code' => 'required|string|max:512',
        ];
    }

    public function messages()
    {
        return [
            'code.required' => '授权码不能为空',
            'code.string' => '授权码格式错误',
            'code.max' => '授权码格式错误',
        ];
    }
}
