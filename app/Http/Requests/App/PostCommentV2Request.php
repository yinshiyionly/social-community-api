<?php

namespace App\Http\Requests\App;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 新版发表评论请求验证
 */
class PostCommentV2Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'postId' => 'required|integer',
            'content' => 'required|string|max:500',
            'parentCommentId' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'postId.required' => '帖子ID不能为空',
            'postId.integer' => '帖子ID格式错误',
            'content.required' => '评论内容不能为空',
            'content.max' => '评论内容不能超过500字',
        ];
    }
}
