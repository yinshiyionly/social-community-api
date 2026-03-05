<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class LiveChapterUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'courseId' => 'required|integer',
            'chapterId' => 'required|integer',
            'chapterTitle' => 'required|string|max:100',
            'liveStartTime' => 'required|date',
            'liveEndTime' => 'required|date|after:live_start_time',
            'isFree' => 'required|integer|in:0,1',
            'roomId' => 'required|integer|exists:app_live_room,room_id',
        ];
    }

    public function messages()
    {
        return [
            'courseId.required' => '请选择课程。',
            'courseId.integer' => '课程不存在。',
            'chapterId.required' => '请选择章节。',
            'chapterId.integer' => '章节不存在。',
            'chapterTitle.required' => '章节标题不能为空。',
            'chapterTitle.max' => '章节标题不能超过100个字符。',
            'liveStartTime.required' => '直播开始时间不能为空。',
            'liveStartTime.date' => '直播开始时间格式不正确。',
            'liveEndTime.required' => '直播结束时间不能为空。',
            'liveEndTime.after' => '直播结束时间必须晚于开始时间。',
            'isFree.required' => '请选择是否免费试看。',
            'isFree.in' => '免费试看值不正确。',
            'roomId.required' => '请选择直播间。',
            'roomId.exists' => '直播间不存在。',
        ];
    }
}
