<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AppConfigStatusRequest extends FormRequest
{
    /**
     * Admin 模块鉴权由路由中间件统一处理，请求层直接放行。
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * 状态修改参数校验。
     *
     * @return array<string, string>
     */
    public function rules()
    {
        return [
            'configId' => 'required|integer|min:1|exists:app_config,config_id',
            'isEnabled' => 'required|boolean',
        ];
    }

    /**
     * 状态修改参数错误文案。
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'configId.required' => '配置ID不能为空',
            'configId.integer' => '配置ID必须为整数',
            'configId.exists' => '配置不存在',
            'isEnabled.required' => '启用状态不能为空',
            'isEnabled.boolean' => '启用状态必须为布尔值',
        ];
    }
}
