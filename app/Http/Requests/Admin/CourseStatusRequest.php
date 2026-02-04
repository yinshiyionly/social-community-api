<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CourseStatusRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'courseId' => 'required|integer|min:1',
            'status' => 'required|integer|in:0,1,2',
        ];
    }

    public function messages()
    {
        return [
            'courseId.required' => '课程ID不能为空',
            'courseId.integer' => '课程ID必须是整数',
            'status.required' => '状态不能为空',
            'status.in' => '状态值无效',
        ];
    }
}
