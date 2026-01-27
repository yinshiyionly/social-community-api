<?php

namespace App\Http\Requests\App;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 关注用户帖子列表请求验证
 */
class FollowPostListRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'page' => 'sometimes|integer|min:1',
            'pageSize' => 'sometimes|integer|min:1|max:50',
        ];
    }

    public function messages()
    {
        return [
            'page.integer' => '页码必须是整数',
            'page.min' => '页码最小为1',
            'pageSize.integer' => '每页数量必须是整数',
            'pageSize.min' => '每页数量最小为1',
            'pageSize.max' => '每页数量最大为50',
        ];
    }
}
