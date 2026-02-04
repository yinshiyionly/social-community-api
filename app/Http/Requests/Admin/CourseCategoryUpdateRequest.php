<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CourseCategoryUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'categoryId' => 'required|integer|min:1',
            'parentId' => 'nullable|integer|min:0',
            'categoryName' => 'required|string|max:50',
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
            'categoryId.required' => '分类ID不能为空',
            'categoryId.integer' => '分类ID必须是整数',
            'categoryName.required' => '分类名称不能为空',
            'categoryName.max' => '分类名称不能超过50个字符',
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
