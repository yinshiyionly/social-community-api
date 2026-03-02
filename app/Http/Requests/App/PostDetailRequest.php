<?php

namespace App\Http\Requests\App;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 帖子详情请求验证
 */
class PostDetailRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'postId' => 'required|numeric|min:1',
            'postType' => 'sometimes|integer|in:1,2,3',
        ];
    }

    public function messages()
    {
        return [
            'postId.required' => 'postId不能为空',
            'postId.numeric' => 'postId格式错误',
            'postId.min' => 'postId必须大于0',
            'postType.integer' => 'postType必须是整数',
            'postType.in' => 'postType仅支持1(图文)、2(视频)、3(文章)',
        ];
    }
}
