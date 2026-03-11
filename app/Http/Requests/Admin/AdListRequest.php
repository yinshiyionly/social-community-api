<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Admin 广告预览列表请求验证。
 *
 * 约束说明：
 * 1. spaceCode 为广告位业务唯一编码，缺失时无法定位广告位；
 * 2. platform 仅允许已定义的平台枚举，避免传入非法值导致筛选异常。
 */
class AdListRequest extends FormRequest
{
    /**
     * 是否允许当前用户发起请求。
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * 请求参数校验规则。
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'spaceCode' => 'required|string|max:50',
            'platform' => 'nullable|integer|in:0,1,2',
        ];
    }

    /**
     * 自定义校验错误文案。
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'spaceCode.required' => '广告位编码不能为空',
            'spaceCode.string' => '广告位编码格式错误',
            'spaceCode.max' => '广告位编码不能超过50个字符',
            'platform.integer' => '平台类型必须是整数',
            'platform.in' => '平台类型无效',
        ];
    }
}
