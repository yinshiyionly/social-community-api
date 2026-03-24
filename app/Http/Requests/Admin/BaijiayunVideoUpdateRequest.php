<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BaijiayunVideoUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'videoId'       => [
                'required',
                'integer',
                'min:1',
                Rule::exists('app_video_baijiayun', 'video_id')->whereNull('deleted_at'),
            ],
            'name'          => 'required|string|max:255',
            'publishStatus' => 'required|integer|in:0,1',
        ];
    }

    public function messages()
    {
        return [
            'videoId.required'       => '视频ID不能为空',
            'videoId.integer'        => '视频ID必须是整数',
            'videoId.min'            => '视频ID必须大于0',
            'videoId.exists'         => '视频不存在或已删除',
            'name.required'          => '视频标题不能为空',
            'name.max'               => '视频标题不能超过255个字符',
            'publishStatus.required' => '发布状态值不能为空',
            'publishStatus.in'       => '发布状态值无效',
        ];
    }
}
