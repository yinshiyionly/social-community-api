<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CourseUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'courseId' => 'required|integer|min:1',
            'categoryId' => 'nullable|integer|min:1',
            'courseTitle' => 'nullable|string|max:200',
            'courseSubtitle' => 'nullable|string|max:300',
            'payType' => 'nullable|integer|in:1,2,3,4',
            'playType' => 'nullable|integer|in:1,2,3,4',
            'scheduleType' => 'nullable|integer|in:1,2',
            'coverImage' => 'nullable|string|max:500',
            'coverVideo' => 'nullable|string|max:500',
            'bannerImages' => 'nullable|array',
            'bannerImages.*' => 'string|max:500',
            'introVideo' => 'nullable|string|max:500',
            'brief' => 'nullable|string',
            'description' => 'nullable|string',
            'suitableCrowd' => 'nullable|string',
            'learnGoal' => 'nullable|string',
            'teacherId' => 'nullable|integer|min:1',
            'assistantIds' => 'nullable|array',
            'assistantIds.*' => 'integer|min:1',
            'originalPrice' => 'nullable|numeric|min:0|max:9999999.99',
            'currentPrice' => 'nullable|numeric|min:0|max:9999999.99',
            'pointPrice' => 'nullable|integer|min:0',
            'isFree' => 'nullable|integer|in:0,1',
            'validDays' => 'nullable|integer|min:0',
            'allowDownload' => 'nullable|integer|in:0,1',
            'allowComment' => 'nullable|integer|in:0,1',
            'allowShare' => 'nullable|integer|in:0,1',
            'sortOrder' => 'nullable|integer|min:0',
            'isRecommend' => 'nullable|integer|in:0,1',
            'isHot' => 'nullable|integer|in:0,1',
            'isNew' => 'nullable|integer|in:0,1',
        ];
    }

    public function messages()
    {
        return [
            'courseId.required' => '课程ID不能为空',
            'courseId.integer' => '课程ID必须是整数',
            'categoryId.integer' => '分类ID必须是整数',
            'courseTitle.max' => '课程标题不能超过200个字符',
            'courseSubtitle.max' => '课程副标题不能超过300个字符',
            'payType.in' => '付费类型无效',
            'playType.in' => '播放类型无效',
            'scheduleType.in' => '排课类型无效',
            'coverImage.max' => '封面图地址不能超过500个字符',
            'originalPrice.numeric' => '原价必须是数字',
            'originalPrice.min' => '原价不能小于0',
            'currentPrice.numeric' => '现价必须是数字',
            'currentPrice.min' => '现价不能小于0',
            'pointPrice.integer' => '积分价格必须是整数',
            'validDays.integer' => '有效期天数必须是整数',
            'sortOrder.integer' => '排序必须是整数',
        ];
    }
}
