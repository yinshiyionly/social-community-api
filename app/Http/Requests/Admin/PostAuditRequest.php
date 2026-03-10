<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 帖子审核请求验证。
 */
class PostAuditRequest extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    public function rules()
    {
        return [
            'postId' => 'required|integer|min:1',
            'status' => 'required|integer|in:1,2',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'postId.required' => '帖子ID不能为空',
            'postId.integer' => '帖子ID必须是整数',
            'postId.min' => '帖子ID必须大于0',
            'status.required' => '审核状态不能为空',
            'status.integer' => '审核状态必须是整数',
            'status.in' => '审核状态值不正确',
        ];
    }
}
