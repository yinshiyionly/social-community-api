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
            'parentId' => 'nullable|integer|min:0',
            'categoryName' => [
                'required',
                'string',
                'max:50',
                Rule::unique('app_course_category', 'category_name')->whereNull('deleted_at'),
            ],
            'categoryCode' => 'nullable|string|max:50',
            'icon' => 'nullable|string|max:255',
            'cover' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
            'sortOrder' => 'nullable|integer|min:0',
            'status' => 'nullable|integer|in:1,2',
        ];
    }

    public function messages()
    {
        return [
            'categoryName.required' => '分类名称不能为空',
            'categoryName.max' => '分类名称不能超过50个字符',
            'categoryName.unique' => '分类名称已存在',
            'categoryCode.max' => '分类编码不能超过50个字符',
            'icon.max' => '图标地址不能超过255个字符',
            'cover.max' => '封面地址不能超过255个字符',
            'description.max' => '分类描述不能超过500个字符',
            'sortOrder.integer' => '排序必须是整数',
            'sortOrder.min' => '排序不能小于0',
            'status.in' => '状态值无效',
        ];
    }
}
