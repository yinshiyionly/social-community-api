<?php

declare(strict_types=1);

namespace App\Http\Requests\Detection\Task;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 更新监测任务请求验证
 *
 * 验证更新监测任务时的请求参数
 */
class UpdateDetectionTaskRequest extends FormRequest
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

        ];
    }
}
