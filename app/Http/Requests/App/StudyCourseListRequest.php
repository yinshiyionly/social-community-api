<?php

namespace App\Http\Requests\App;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 学习页课程列表筛选请求验证
 */
class StudyCourseListRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'filter' => 'required|string',
            'filterType' => 'nullable|string|in:quick,allType',
            'page' => 'required|integer|min:1',
            'pageSize' => 'required|integer|min:1|max:50',
        ];
    }

    public function messages()
    {
        return [
            'filter.required' => '筛选值不能为空。',
            'filterType.in' => '筛选类型不正确。',
            'page.required' => '页码不能为空。',
            'page.min' => '页码最小为1。',
            'pageSize.required' => '每页条数不能为空。',
            'pageSize.max' => '每页条数最大为50。',
        ];
    }
}
