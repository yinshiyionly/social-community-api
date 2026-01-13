<?php

namespace App\Http\Requests\App;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 广告列表请求验证
 */
class AdListRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'spaceCode' => 'required|string|max:50',
            'platform' => 'nullable|integer|in:0,1,2',
        ];
    }

    public function messages()
    {
        return [
            'spaceCode.required' => '参数错误',
            'spaceCode.string' => '参数错误',
            'spaceCode.max' => '参数错误',
            'platform.integer' => '参数错误',
            'platform.in' => '参数错误',
        ];
    }
}
