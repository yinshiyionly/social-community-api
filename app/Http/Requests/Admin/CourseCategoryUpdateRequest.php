<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CourseCategoryUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $categoryId = $this->input('categoryId');

        return [
            'categoryId' => 'required|integer|min:1',
            'categoryName' => [
                'required',
                'string',
                'max:50',
                Rule::unique('app_course_category', 'category_name')
                    ->whereNull('deleted_at')
                    ->ignore($categoryId, 'category_id'),
            ],
            'icon' => 'required|string|max:255',
            'status' => 'required|integer|in:1,2',
        ];
    }

    public function messages()
    {
        return [
            'categoryId.required' => '分类ID不能为空',
            'categoryId.integer' => '分类ID必须是整数',
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
