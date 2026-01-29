<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 设置话题推荐状态请求验证
 */
class TopicRecommendRequest extends FormRequest
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
            'isRecommend' => 'required|in:0,1',
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
            'isRecommend.required' => '推荐状态不能为空',
            'isRecommend.in' => '推荐状态值不正确',
        ];
    }
}
