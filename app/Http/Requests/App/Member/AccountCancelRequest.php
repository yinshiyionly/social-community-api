<?php

namespace App\Http\Requests\App\Member;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 注销账号请求参数验证。
 *
 * 规则：
 * 1. confirmText 可不传，若传入必须严格等于“注销账号”；
 * 2. reason 仅用于审计日志记录，长度限制 200 字以内。
 */
class AccountCancelRequest extends FormRequest
{
    /**
     * 允许已登录会员发起注销请求。
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 获取参数校验规则。
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'confirmText' => 'sometimes|string|in:注销账号',
            'reason' => 'nullable|string|max:200',
        ];
    }

    /**
     * 获取参数校验错误提示。
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'confirmText.in' => '确认信息不正确',
            'confirmText.string' => '确认信息不正确',
            'reason.string' => '注销原因格式不正确',
            'reason.max' => '注销原因长度不能超过200个字符',
        ];
    }
}
