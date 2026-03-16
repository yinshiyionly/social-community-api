<?php

namespace App\Http\Requests\Admin;

use App\Models\App\AppConfig;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AppConfigUpdateRequest extends FormRequest
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
     * 更新配置参数校验。
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $configId = (int) $this->input('configId');

        return [
            'configId' => 'required|integer|min:1|exists:app_config,config_id',
            'configKey' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9._-]+$/',
                Rule::unique('app_config', 'config_key')
                    ->where(function ($query) {
                        return $query
                            ->where('env', $this->input('env'))
                            ->where('platform', $this->input('platform'));
                    })
                    ->ignore($configId, 'config_id'),
            ],
            'configName' => 'required|string|max:100',
            'configType' => [
                'required',
                'string',
                Rule::in([
                    AppConfig::TYPE_BOOL,
                    AppConfig::TYPE_NUMBER,
                    AppConfig::TYPE_STRING,
                    AppConfig::TYPE_JSON,
                    AppConfig::TYPE_ARRAY,
                ]),
            ],
            'groupName' => 'nullable|string|max:50',
            'configValue' => 'present',
            'visibilityMode' => [
                'required',
                'string',
                Rule::in([
                    AppConfig::VISIBILITY_MODE_ALWAYS,
                    AppConfig::VISIBILITY_MODE_WINDOW,
                ]),
            ],
            'timezone' => 'required|string|max:64',
            'windows' => 'nullable|array',
            'windows.*' => 'required|array',
            'windows.*.startAt' => 'required|date',
            'windows.*.endAt' => 'required|date',
            'windows.*.visible' => 'required|boolean',
            'isEnabled' => 'nullable|boolean',
            'sortNum' => 'nullable|integer|min:0',
            'env' => 'required|string|max:20',
            'platform' => 'required|string|max:20',
            'description' => 'nullable|string',
        ];
    }

    /**
     * 更新配置参数错误文案。
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'configId.required' => '配置ID不能为空',
            'configId.integer' => '配置ID必须为整数',
            'configId.exists' => '配置不存在',
            'configKey.required' => '配置键不能为空',
            'configKey.max' => '配置键长度不能超过100个字符',
            'configKey.regex' => '配置键仅支持字母、数字、点、下划线和横线',
            'configKey.unique' => '同环境同平台下配置键已存在',
            'configName.required' => '配置名称不能为空',
            'configName.max' => '配置名称长度不能超过100个字符',
            'configType.required' => '配置类型不能为空',
            'configType.in' => '配置类型不合法',
            'groupName.max' => '分组名称长度不能超过50个字符',
            'configValue.present' => '配置值字段必须传入',
            'visibilityMode.required' => '显隐模式不能为空',
            'visibilityMode.in' => '显隐模式不合法',
            'timezone.required' => '时区不能为空',
            'timezone.max' => '时区长度不能超过64个字符',
            'windows.array' => '时间窗必须为数组',
            'windows.*.array' => '时间窗项格式不正确',
            'windows.*.startAt.required' => '时间窗开始时间不能为空',
            'windows.*.startAt.date' => '时间窗开始时间格式不正确',
            'windows.*.endAt.required' => '时间窗结束时间不能为空',
            'windows.*.endAt.date' => '时间窗结束时间格式不正确',
            'windows.*.visible.required' => '时间窗可见状态不能为空',
            'windows.*.visible.boolean' => '时间窗可见状态必须为布尔值',
            'isEnabled.boolean' => '启用状态必须为布尔值',
            'sortNum.integer' => '排序值必须为整数',
            'sortNum.min' => '排序值不能小于0',
            'env.required' => '环境标识不能为空',
            'env.max' => '环境标识长度不能超过20个字符',
            'platform.required' => '平台标识不能为空',
            'platform.max' => '平台标识长度不能超过20个字符',
        ];
    }

    /**
     * 追加跨字段校验。
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $visibilityMode = $this->input('visibilityMode');
            $windows = $this->input('windows', []);

            if ($visibilityMode === AppConfig::VISIBILITY_MODE_WINDOW && empty($windows)) {
                $validator->errors()->add('windows', 'window 模式下必须至少配置一个时间窗');
                return;
            }

            if (!is_array($windows)) {
                return;
            }

            foreach ($windows as $index => $window) {
                if (!is_array($window)) {
                    continue;
                }

                $startAt = $window['startAt'] ?? null;
                $endAt = $window['endAt'] ?? null;

                if ($startAt === null || $endAt === null) {
                    continue;
                }

                $startTs = strtotime((string) $startAt);
                $endTs = strtotime((string) $endAt);

                if ($startTs !== false && $endTs !== false && $endTs <= $startTs) {
                    $validator->errors()->add(
                        'windows.' . $index . '.endAt',
                        '第' . ($index + 1) . '个时间窗结束时间必须晚于开始时间'
                    );
                }
            }
        });
    }
}
