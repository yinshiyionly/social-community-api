<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 新增话题请求验证
 */
class TopicStoreRequest extends FormRequest
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
            'topicName' => 'required|string|max:100|unique:app_topic_base,topic_name',
            'coverUrl' => 'required|string|max:500',
            'description' => 'nullable|string|max:500',
            'detailHtml' => 'nullable|string',
            'sortNum' => 'nullable|integer|min:0',
            'isRecommend' => 'nullable|in:0,1',
            'isOfficial' => 'nullable|in:0,1',
            //'status' => 'required|in:1,2',
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
            'topicName.required' => '话题名称不能为空',
            'topicName.string' => '话题名称必须是字符串',
            'topicName.max' => '话题名称不能超过100个字符',
            'topicName.unique' => '话题名称已存在',
            'coverUrl.required' => '封面图不能为空',
            'coverUrl.string' => '封面图URL必须是字符串',
            'coverUrl.max' => '封面图URL不能超过500个字符',
            'description.string' => '话题简介必须是字符串',
            'description.max' => '话题简介不能超过500个字符',
            'detailHtml.string' => '话题详情必须是字符串',
            'sortNum.integer' => '排序号必须是整数',
            'sortNum.min' => '排序号不能小于0',
            'isRecommend.in' => '推荐状态值不正确',
            'isOfficial.in' => '官方状态值不正确',
            'status.required' => '状态不能为空',
            'status.in' => '状态值不正确',
        ];
    }
}
