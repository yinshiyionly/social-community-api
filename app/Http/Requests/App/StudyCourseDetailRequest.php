<?php

namespace App\Http\Requests\App;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 学习中心课程详情请求参数校验。
 *
 * 校验目标：
 * 1. courseId 必填且为正整数；
 * 2. planKey 仅允许 preview 或日期字符串（Y-m-d）。
 */
class StudyCourseDetailRequest extends FormRequest
{
    /**
     * 课程详情接口鉴权由路由中间件 app.auth 负责。
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * 请求参数规则。
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'courseId' => 'required|integer|min:1',
            'planKey' => ['nullable', 'string', 'regex:/^(preview|\d{4}-\d{2}-\d{2})$/'],
        ];
    }

    /**
     * 参数校验失败提示。
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'courseId.required' => '课程ID不能为空',
            'courseId.integer' => '课程ID格式错误',
            'courseId.min' => '课程ID格式错误',
            'planKey.regex' => 'planKey格式错误',
        ];
    }
}
