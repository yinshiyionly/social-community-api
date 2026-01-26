<?php

namespace App\Http\Requests\App;

use Illuminate\Foundation\Http\FormRequest;

class PostCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => 'required|string|max:500',
            'parent_id' => 'nullable|integer|min:0',
            'reply_to_member_id' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => '评论内容不能为空',
            'content.max' => '评论内容不能超过500字',
        ];
    }
}
