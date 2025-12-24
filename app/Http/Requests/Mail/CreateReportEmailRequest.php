<?php

namespace App\Http\Requests\Mail;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 举报邮箱表单验证
 */
class CreateReportEmailRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'email' => ['required', 'email', 'max:80', Rule::unique('report_email', 'email')->whereNull('deleted_at')],
            'auth_code' => 'required|string|max:80',
            'smtp_host' => 'required|string|max:80',
            'smtp_port' => 'required|integer|min:1|max:65535',
            'status' => 'sometimes|integer|in:0,1',
        ];
    }

    public function messages()
    {
        return [
            'email.required' => '邮箱地址不能为空',
            'email.email' => '邮箱格式不正确',
            'email.max' => '邮箱最大支持80个字符',
            'email.unique' => '邮箱已存在',
            'auth_code.required' => '授权码不能为空',
            'auth_code.string' => '授权码格式错误',
            'auth_code.max' => '授权码最大支持80个字符',
            'smtp_host.required' => 'SMTP服务器地址不能为空',
            'smtp_host.string' => 'SMTP服务器地址格式错误',
            'smtp_host.max' => 'SMTP服务器最大支持80个字符',
            'smtp_port.required' => 'SMTP端口不能为空',
            'smtp_port.integer' => 'SMTP端口必须是整数',
            'smtp_port.min' => 'SMTP端口有效值为1~65535',
            'smtp_port.max' => 'SMTP端口有效值为1~65535'
        ];
    }
}
