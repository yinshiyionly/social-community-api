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
            'filter' => 'nullable|integer',
            'filterType' => 'nullable|integer|in:1,2,3,4',
            'page' => 'nullable|integer|min:1',
            'pageSize' => 'nullable|integer|min:1|max:50',
        ];
    }

    public function messages()
    {
        return [
            'filter.integer' => '课程分类ID必须为整数。',
            'filterType.integer' => '课程付费类型必须为整数。',
            'filterType.in' => '课程付费类型不正确。',
            'page.min' => '页码最小为1。',
            'pageSize.max' => '每页条数最大为50。',
        ];
    }
}

