<?php

namespace App\Http\Requests\App;

use Illuminate\Foundation\Http\FormRequest;

/**
 * App 端课程详情相关接口请求验证。
 *
 * 职责：
 * 1. 校验课程 ID 参数合法性；
 * 2. 为 has-chapters/detail-legacy/detail-chapters 提供统一参数读取入口。
 */
class CourseDetailRequest extends FormRequest
{
    /**
     * 详情类接口鉴权由路由中间件控制，请求验证层统一放行。
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
     * @return array<string, string>
     */
    public function rules()
    {
        return [
            'id' => 'required|integer|min:1',
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
            'id.required' => '课程ID不能为空',
            'id.integer' => '课程ID格式错误',
            'id.min' => '课程ID格式错误',
        ];
    }

    /**
     * 获取课程 ID。
     *
     * @return int
     */
    public function getCourseId(): int
    {
        return (int)$this->input('id');
    }
}
