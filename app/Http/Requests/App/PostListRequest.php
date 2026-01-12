<?php

namespace App\Http\Requests\App;

use Illuminate\Foundation\Http\FormRequest;

class PostListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cursor' => 'nullable|integer|min:0',
            'limit' => 'nullable|integer|min:1|max:50',
            'member_id' => 'nullable|integer|min:1',
            'post_type' => 'nullable|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'cursor.integer' => '游标必须是整数',
            'cursor.min' => '游标不能小于0',
            'limit.integer' => '每页数量必须是整数',
            'limit.min' => '每页数量最少为1',
            'limit.max' => '每页数量最多为50',
            'member_id.integer' => '用户ID必须是整数',
            'member_id.min' => '用户ID不能小于1',
            'post_type.integer' => '动态类型必须是整数',
            'post_type.min' => '动态类型不能小于1',
        ];
    }
}
