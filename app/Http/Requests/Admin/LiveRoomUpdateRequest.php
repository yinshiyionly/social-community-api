<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class LiveRoomUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'roomId'             => 'required|integer|min:1',
            'roomTitle'          => 'nullable|string|max:200',
            'roomCover'          => 'nullable|string|max:500',
            'scheduledStartTime' => 'nullable|date',
            'scheduledEndTime'   => 'nullable|date|after:scheduledStartTime'
        ];
    }

    public function messages()
    {
        return [
            'roomId.required'              => '直播间ID不能为空',
            'roomId.integer'               => '直播间ID必须是整数',
            'roomId.min'                   => '直播间ID无效',
            'roomTitle.max'                => '直播间标题不能超过200个字符',
            'roomCover.max'                => '封面地址不能超过500个字符',
            'scheduledStartTime.date'      => '计划开始时间格式无效',
            'scheduledEndTime.date'        => '计划结束时间格式无效',
            'scheduledEndTime.after'       => '计划结束时间必须晚于开始时间',
        ];
    }
}
