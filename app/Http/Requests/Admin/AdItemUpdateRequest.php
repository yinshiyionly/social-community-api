<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdItemUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'adId' => 'required|integer|min:1',
            'spaceId' => 'required|integer|min:1',
            'adTitle' => 'required|string|max:100',
            'adType' => 'required|string|in:image,video,text,html',
            'contentUrl' => 'nullable|string',
            'targetType' => 'nullable|string|in:external,internal,none',
            'targetUrl' => 'nullable|string|max:500',
            'sortNum' => 'nullable|integer|min:0',
            'status' => 'nullable|integer|in:1,2',
            'startTime' => 'nullable|date',
            'endTime' => 'nullable|date|after_or_equal:startTime',
            'extJson' => 'nullable|array',
        ];
    }

    public function messages()
    {
        return [
            'adId.required' => '广告ID不能为空',
            'adId.integer' => '广告ID必须是整数',
            'spaceId.required' => '广告位ID不能为空',
            'spaceId.integer' => '广告位ID必须是整数',
            'adTitle.required' => '广告标题不能为空',
            'adTitle.max' => '广告标题不能超过100个字符',
            'adType.required' => '广告素材类型不能为空',
            'adType.in' => '广告素材类型无效',
            'targetType.in' => '跳转类型无效',
            'targetUrl.max' => '跳转地址不能超过500个字符',
            'sortNum.min' => '排序值不能小于0',
            'status.in' => '状态值无效',
            'endTime.after_or_equal' => '失效时间不能早于生效时间',
        ];
    }
}
