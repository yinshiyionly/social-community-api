<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Traits\CourseUpsertRequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class CourseUpdateRequest extends FormRequest
{
    use CourseUpsertRequestTrait;

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return array_merge([
            'courseId' => 'required|integer|min:1',
        ], $this->courseUpsertRules());
    }

    public function messages()
    {
        return array_merge([
            'courseId.required' => '课程ID不能为空',
            'courseId.integer' => '课程ID必须是整数',
        ], $this->courseUpsertMessages());
    }
}
