<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CourseCategoryStatusRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'categoryId' => 'required|integer|min:1',
            'status' => 'required|integer|in:1,2',
        ];
    }

    public function messages()
    {
        return [
            'categoryId.required' => '分类ID不能为空',
            'categoryId.integer' => '分类ID必须是整数',
            'status.required' => '状态不能为空',
            'status.in' => '状态值无效',
        ];
    }
}
