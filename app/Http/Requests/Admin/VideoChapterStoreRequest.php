<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class VideoChapterStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'courseId' => 'required|integer',
            'chapterTitle' => 'required|string|max:100',
            'unlockTime' => 'nullable|date',
            'isFreeTrial' => 'required|integer|in:0,1',
            'videoIds' => 'nullable|array',
            'videoIds.*' => 'integer',
        ];
    }

    public function messages()
    {
        return [
            'courseId.required' => '课程ID不能为空。',
            'chapterTitle.required' => '章节标题不能为空。',
            'chapterTitle.max' => '章节标题不能超过100个字符。',
            'isFreeTrial.required' => '请选择是否免费试看。',
            'isFreeTrial.in' => '免费试看参数不正确。',
        ];
    }
}
