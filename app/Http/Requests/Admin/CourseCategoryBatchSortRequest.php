<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CourseCategoryBatchSortRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'category' => 'required|array|min:1',
            'category.*.categoryId' => 'required|integer|min:1|distinct',
            'category.*.categorySort' => 'required|integer|min:0',
        ];
    }

    public function messages()
    {
        return [
            'category.required' => '排序数据不能为空',
            'category.array' => '排序数据格式不正确',
            'category.min' => '排序数据不能为空',
            'category.*.categoryId.required' => '分类ID不能为空',
            'category.*.categoryId.integer' => '分类ID必须是整数',
            'category.*.categoryId.min' => '分类ID必须大于0',
            'category.*.categoryId.distinct' => '分类ID不能重复',
            'category.*.categorySort.required' => '排序值不能为空',
            'category.*.categorySort.integer' => '排序值必须是整数',
            'category.*.categorySort.min' => '排序值不能小于0',
        ];
    }
}
