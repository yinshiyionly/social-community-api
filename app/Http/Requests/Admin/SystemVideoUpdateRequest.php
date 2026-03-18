<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SystemVideoUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'videoId' => 'required|integer|min:1',
            'name'    => 'required|string|max:255',
            'status'  => 'nullable|integer|in:1,2'
        ];
    }

    public function messages()
    {
        return [
            'videoId.required' => '视频ID不能为空',
            'videoId.integer'  => '视频ID必须是整数',
            'videoId.min'      => '视频ID必须大于0',
            'name.required'    => '视频标题不能为空',
            'name.max'         => '视频标题不能超过255个字符',
            'status.in'        => '状态值无效'
        ];
    }
}
