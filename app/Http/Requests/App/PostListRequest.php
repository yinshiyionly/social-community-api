<?php

namespace App\Http\Requests\App;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 帖子列表请求验证（游标分页）
 */
class PostListRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'cursor' => 'sometimes|nullable|string',
            'pageSize' => 'sometimes|integer|min:1|max:50',
        ];
    }

    public function messages()
    {
        return [
            'cursor.string' => '游标格式错误',
            'pageSize.integer' => '每页数量必须是整数',
            'pageSize.min' => '每页数量最小为1',
            'pageSize.max' => '每页数量最大为50',
        ];
    }
}
