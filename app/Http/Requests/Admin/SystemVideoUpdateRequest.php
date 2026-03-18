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
            'videoId'    => 'required|integer|min:1',
            'name'       => 'required|string|max:255',
            'status'     => 'nullable|integer|in:1,2',
            'totalSize'  => 'required|string|max:50',
            'prefaceUrl' => 'required|string|max:1024',
            'playUrl'    => 'required|string|max:512',
            'length'     => 'required|integer|min:0',
            'width'      => 'required|integer|min:0',
            'height'     => 'required|integer|min:0',
        ];
    }

    public function messages()
    {
        return [
            'videoId.required'    => '视频ID不能为空',
            'videoId.integer'     => '视频ID必须是整数',
            'videoId.min'         => '视频ID必须大于0',
            'name.required'       => '视频标题不能为空',
            'name.max'            => '视频标题不能超过255个字符',
            'status.in'           => '状态值无效',
            'totalSize.required'  => '视频大小不能为空',
            'totalSize.max'       => '视频大小不能超过50个字符',
            'prefaceUrl.required' => '封面地址不能为空',
            'prefaceUrl.max'      => '封面地址不能超过1024个字符',
            'playUrl.required'    => '播放地址不能为空',
            'playUrl.max'         => '播放地址不能超过512个字符',
            'length.required'     => '视频时长不能为空',
            'length.integer'      => '视频时长必须是整数',
            'length.min'          => '视频时长不能小于0',
            'width.required'      => '视频宽度不能为空',
            'width.integer'       => '视频宽度必须是整数',
            'width.min'           => '视频宽度不能小于0',
            'height.required'     => '视频高度不能为空',
            'height.integer'      => '视频高度必须是整数',
            'height.min'          => '视频高度不能小于0',
        ];
    }
}
