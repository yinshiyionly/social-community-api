<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdSpaceStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'spaceName' => 'required|string|max:50',
            'spaceCode' => [
                'required',
                'string',
                'max:50',
                Rule::unique('app_ad_space', 'space_code')->whereNull('deleted_at'),
            ],
            'platform' => 'nullable|integer|in:0,1,2',
            'width' => 'nullable|integer|min:0',
            'height' => 'nullable|integer|min:0',
            'maxAds' => 'nullable|integer|min:0',
            'status' => 'nullable|integer|in:1,2',
        ];
    }

    public function messages()
    {
        return [
            'spaceName.required' => '广告位名称不能为空',
            'spaceName.max' => '广告位名称不能超过50个字符',
            'spaceCode.required' => '广告位编码不能为空',
            'spaceCode.max' => '广告位编码不能超过50个字符',
            'spaceCode.unique' => '广告位编码已存在',
            'platform.in' => '平台值无效',
            'width.min' => '宽度不能小于0',
            'height.min' => '高度不能小于0',
            'maxAds.min' => '最大广告数不能小于0',
            'status.in' => '状态值无效',
        ];
    }
}
