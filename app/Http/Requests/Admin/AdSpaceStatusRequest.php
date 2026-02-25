<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdSpaceStatusRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'spaceId' => 'required|integer|min:1',
            'status' => 'required|integer|in:1,2',
        ];
    }

    public function messages()
    {
        return [
            'spaceId.required' => '广告位ID不能为空',
            'spaceId.integer' => '广告位ID必须是整数',
            'status.required' => '状态不能为空',
            'status.in' => '状态值无效',
        ];
    }
}
