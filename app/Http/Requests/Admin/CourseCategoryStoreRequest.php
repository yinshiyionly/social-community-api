<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CourseCategoryStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'categoryName' => [
                'required',
                'string',
                'max:50',
                Rule::unique('app_course_category', 'category_name')->whereNull('deleted_at'),
            ],
            'icon' => 'required|string|max:255',
            'status' => 'required|integer|in:1,2',
        ];
    }

    public function messages()
    {
        return [
            'categoryName.required' => '分类名称不能为空',
            'categoryName.max' => '分类名称不能超过50个字符',
            'categoryName.unique' => '分类名称已存在',
            'icon.required' => '图标不能为空',
            'icon.max' => '图标地址不能超过255个字符',
            'status.required' => '状态不能为空',
            'status.in' => '状态值无效',
        ];
    }
}
