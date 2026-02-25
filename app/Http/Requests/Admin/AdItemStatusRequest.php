<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdItemStatusRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'adId' => 'required|integer|min:1',
            'status' => 'required|integer|in:1,2',
        ];
    }

    public function messages()
    {
        return [
            'adId.required' => '广告ID不能为空',
            'adId.integer' => '广告ID必须是整数',
            'status.required' => '状态不能为空',
            'status.in' => '状态值无效',
        ];
    }
}
