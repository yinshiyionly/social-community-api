<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 修改话题状态请求验证
 */
class TopicStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'topicId' => 'required|exists:app_topic_base,topic_id',
            'status' => 'required|in:1,2',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'topicId.required' => '话题ID不能为空',
            'topicId.exists' => '话题不存在',
            'status.required' => '状态不能为空',
            'status.in' => '状态值不正确',
        ];
    }
}
