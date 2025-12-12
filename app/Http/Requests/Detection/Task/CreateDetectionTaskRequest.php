<?php

declare(strict_types=1);

namespace App\Http\Requests\Detection\Task;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 创建监测任务请求验证
 *
 * 验证创建监测任务时的请求参数
 */
class CreateDetectionTaskRequest extends FormRequest
{
    /**
     * 确定用户是否有权发出此请求
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 获取适用于请求的验证规则
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            // 任务名称
            'task_name' => ['required', 'string', 'max:255'],

            // 文本关键词
            'text_plain' => ['required', 'string', 'max:100'],

            // 地域配置
            'based_location_plain' => ['nullable', 'array'],
            'based_location_plain.region' => ['nullable', 'string', 'max:100'],
            'based_location_plain.province' => ['nullable', 'string', 'max:100'],
            'based_location_plain.city' => ['nullable', 'string', 'max:100'],
            'based_location_plain.district' => ['nullable', 'string', 'max:100'],

            // 行业标签
            'tag_plain' => ['nullable', 'array'],
            'tag_plain.*' => ['string', 'max:100'],

            // 数据来源平台
            // 'data_site' => ['required', 'array', 'min:1'],
            'data_site.*' => ['string', 'max:50'],

            // 预警名称
            'warn_name' => ['nullable', 'string', 'max:255'],

            // 预警邮箱开关 1-开启 2-关闭
            'warn_publish_email_state' => ['nullable', 'integer', Rule::in([1, 2])],

            // 预警邮箱配置
            'warn_publish_email_config' => [
                'nullable',
                'array',
                'required_if:warn_publish_email_state,1',
            ],
            'warn_publish_email_config.*' => ['email', 'max:255'],

            // 预警微信开关 1-开启 2-关闭
            'warn_publish_wx_state' => ['nullable', 'integer', Rule::in([1, 2])],

            // 预警微信配置
            'warn_publish_wx_config' => [
                'nullable',
                'array',
                'required_if:warn_publish_wx_state,1',
            ],
            'warn_publish_wx_config.*' => ['string', 'max:100'],

            // 预警接收开始时间（当任意预警开启时必填）
            'warn_reception_start_time' => [
                'nullable',
                'date_format:Y-m-d H:i:s',
                'required_if:warn_publish_email_state,1',
                'required_if:warn_publish_wx_state,1',
            ],

            // 预警接收结束时间（当任意预警开启时必填）
            'warn_reception_end_time' => [
                'nullable',
                'date_format:Y-m-d H:i:s',
                'required_if:warn_publish_email_state,1',
                'required_if:warn_publish_wx_state,1',
                'after:warn_reception_start_time',
            ],
        ];
    }

    /**
     * 获取验证错误的自定义消息
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'task_name.required' => '任务名称不能为空',
            'task_name.max' => '任务名称不能超过255个字符',

            'text_plain.required' => '文本关键词不能为空',
            'text_plain.max' => '文本关键词不能超过100个字符',

            'based_location_plain.array' => '地域配置格式错误',
            'based_location_plain.region.max' => '地区名称不能超过100个字符',
            'based_location_plain.province.max' => '省份名称不能超过100个字符',
            'based_location_plain.city.max' => '城市名称不能超过100个字符',
            'based_location_plain.district.max' => '区县名称不能超过100个字符',

            'tag_plain.array' => '行业标签格式错误',
            'tag_plain.*.string' => '行业标签必须是字符串',
            'tag_plain.*.max' => '单个行业标签不能超过100个字符',

            'data_site.required' => '数据来源平台不能为空',
            'data_site.array' => '数据来源平台格式错误',
            'data_site.min' => '至少选择一个数据来源平台',
            'data_site.*.string' => '数据来源平台必须是字符串',

            'warn_name.max' => '预警名称不能超过255个字符',

            'warn_publish_email_state.in' => '预警邮箱开关值无效，只能是1或2',
            'warn_publish_email_config.required_if' => '开启邮箱预警时，预警邮箱配置不能为空',
            'warn_publish_email_config.array' => '预警邮箱配置格式错误',
            'warn_publish_email_config.*.email' => '预警邮箱格式不正确',
            'warn_publish_email_config.*.max' => '邮箱地址不能超过255个字符',

            'warn_publish_wx_state.in' => '预警微信开关值无效，只能是1或2',
            'warn_publish_wx_config.required_if' => '开启微信预警时，预警微信配置不能为空',
            'warn_publish_wx_config.array' => '预警微信配置格式错误',
            'warn_publish_wx_config.*.string' => '微信号必须是字符串',
            'warn_publish_wx_config.*.max' => '微信号不能超过100个字符',

            'warn_reception_start_time.date_format' => '预警接收开始时间格式错误，正确格式：Y-m-d H:i:s',
            'warn_reception_start_time.required_if' => '开启预警时，预警接收开始时间不能为空',

            'warn_reception_end_time.date_format' => '预警接收结束时间格式错误，正确格式：Y-m-d H:i:s',
            'warn_reception_end_time.required_if' => '开启预警时，预警接收结束时间不能为空',
            'warn_reception_end_time.after' => '预警接收结束时间必须晚于开始时间',
        ];
    }

    /**
     * 获取验证属性的自定义名称
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'task_name' => '任务名称',
            'text_plain' => '文本关键词',
            'based_location_plain' => '地域配置',
            'based_location_plain.region' => '地区',
            'based_location_plain.province' => '省份',
            'based_location_plain.city' => '城市',
            'based_location_plain.district' => '区县',
            'tag_plain' => '行业标签',
            'data_site' => '数据来源平台',
            'warn_name' => '预警名称',
            'warn_publish_email_state' => '预警邮箱开关',
            'warn_publish_email_config' => '预警邮箱配置',
            'warn_publish_wx_state' => '预警微信开关',
            'warn_publish_wx_config' => '预警微信配置',
            'warn_reception_start_time' => '预警接收开始时间',
            'warn_reception_end_time' => '预警接收结束时间',
        ];
    }
}
