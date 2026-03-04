<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CourseBatchSortRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'course' => 'required|array|min:1',
            'course.*.courseId' => 'required|integer|min:1|distinct',
            'course.*.courseSort' => 'required|integer|min:0',
        ];
    }

    public function messages()
    {
        return [
            'course.required' => '排序数据不能为空',
            'course.array' => '排序数据格式不正确',
            'course.min' => '排序数据不能为空',
            'course.*.courseId.required' => '课程ID不能为空',
            'course.*.courseId.integer' => '课程ID必须是整数',
            'course.*.courseId.min' => '课程ID必须大于0',
            'course.*.courseId.distinct' => '课程ID不能重复',
            'course.*.courseSort.required' => '排序值不能为空',
            'course.*.courseSort.integer' => '排序值必须是整数',
            'course.*.courseSort.min' => '排序值不能小于0',
        ];
    }
}
