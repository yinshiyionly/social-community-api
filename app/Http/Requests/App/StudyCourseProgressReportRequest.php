<?php

namespace App\Http\Requests\App;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 学习页章节进度上报请求参数校验。
 *
 * 校验目标：
 * 1. courseId 必填且为正整数；
 * 2. chapterId 必填且为正整数；
 * 3. currentPosition 必填且为 hh:mm:ss 格式。
 */
class StudyCourseProgressReportRequest extends FormRequest
{
    /**
     * 学习进度上报接口鉴权由路由中间件 app.auth 负责。
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
            'chapterId' => 'required|integer|min:1',
            'currentPosition' => ['required', 'string', 'regex:/^\d{2}:\d{2}:\d{2}$/'],
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
            'chapterId.required' => '章节ID不能为空',
            'chapterId.integer' => '章节ID格式错误',
            'chapterId.min' => '章节ID格式错误',
            'currentPosition.required' => '播放进度不能为空',
            'currentPosition.regex' => '播放进度格式错误，请使用hh:mm:ss',
        ];
    }
}
