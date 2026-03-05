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
            'name' => 'sometimes|string|max:255',
            'status' => 'sometimes|integer|in:1,2',
            'totalSize' => 'sometimes|string|max:50',
            'prefaceUrl' => 'sometimes|nullable|string|max:1024',
            'playUrl' => 'sometimes|nullable|string|max:512',
            'length' => 'sometimes|integer|min:0',
            'width' => 'sometimes|integer|min:0',
            'height' => 'sometimes|integer|min:0',
        ];
    }

    public function messages()
    {
        return [
            'videoId.required' => '视频ID不能为空',
            'videoId.integer' => '视频ID必须是整数',
            'videoId.min' => '视频ID必须大于0',
            'name.max' => '视频标题不能超过255个字符',
            'status.in' => '状态值无效',
            'totalSize.max' => '视频大小不能超过50个字符',
            'prefaceUrl.max' => '封面地址不能超过1024个字符',
            'playUrl.max' => '播放地址不能超过512个字符',
            'length.integer' => '视频时长必须是整数',
            'length.min' => '视频时长不能小于0',
            'width.integer' => '视频宽度必须是整数',
            'width.min' => '视频宽度不能小于0',
            'height.integer' => '视频高度必须是整数',
            'height.min' => '视频高度不能小于0',
        ];
    }
}
