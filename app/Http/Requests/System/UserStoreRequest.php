<?php

namespace App\Http\Requests\System;

use Illuminate\Foundation\Http\FormRequest;

class UserStoreRequest extends FormRequest
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
            'userName' => 'required|string|max:30|unique:sys_user,user_name',
            'nickName' => 'required|string|max:30',
            'password' => 'required|string|min:6',
            'email' => 'nullable|email|max:50',
            'phonenumber' => 'nullable|string|max:11',
            'sex' => 'nullable|in:0,1,2',
            'status' => 'required|in:0,1',
            'deptId' => 'nullable|exists:sys_dept,dept_id',
            'roleIds' => 'nullable|array',
            'postIds' => 'nullable|array'
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
