<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 课程复制请求校验。
 *
 * 约束：
 * 1. 仅接收被复制课程的 `courseId`；
 * 2. `courseId` 必须是正整数；
 * 3. 课程是否可复制（是否存在、是否录播课）由 Service 统一校验。
 */
class CourseCopyRequest extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    public function rules()
    {
        return [
            'courseId' => 'required|integer|min:1',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'courseId.required' => '课程ID不能为空',
            'courseId.integer' => '课程ID必须是整数',
            'courseId.min' => '课程ID必须大于0',
        ];
    }
}
