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
            'chapter_title' => 'required|string|max:100',
            'live_start_time' => 'required|date',
            'live_end_time' => 'required|date|after:live_start_time',
            'is_free' => 'required|integer|in:0,1',
            'room_id' => 'required|integer|exists:app_live_room,room_id',
        ];
    }

    public function messages()
    {
        return [
            'chapter_title.required' => '章节标题不能为空。',
            'chapter_title.max' => '章节标题不能超过100个字符。',
            'live_start_time.required' => '直播开始时间不能为空。',
            'live_start_time.date' => '直播开始时间格式不正确。',
            'live_end_time.required' => '直播结束时间不能为空。',
            'live_end_time.after' => '直播结束时间必须晚于开始时间。',
            'is_free.required' => '请选择是否免费试看。',
            'is_free.in' => '免费试看值不正确。',
            'room_id.required' => '请选择直播间。',
            'room_id.exists' => '直播间不存在。',
        ];
    }
}
