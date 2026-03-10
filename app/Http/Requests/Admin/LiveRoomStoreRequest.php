<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class LiveRoomStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $liveType = (int) $this->input('liveType');

        $rules = [
            'roomTitle'          => 'required|string|max:200',
            'liveType'           => 'required|integer|in:1,2',
            'roomCover'          => 'required|string|max:500',
            'roomIntro'          => 'nullable|string|max:5000',
            'anchorName'         => 'nullable|string|max:100',
            'anchorAvatar'       => 'nullable|string|max:500',
            'scheduledStartTime' => 'nullable|date',
            'scheduledEndTime'   => 'nullable|date|after:scheduledStartTime',
        ];

        return $rules;
    }

    public function messages()
    {
        return [
            'roomTitle.required'           => '直播间标题不能为空',
            'roomTitle.max'                => '直播间标题不能超过200个字符',
            'liveType.required'            => '直播类型不能为空',
            'liveType.in'                  => '直播类型值无效',
            'roomCover.required'           => '直播封面不能为空',
            'roomCover.max'                => '封面地址不能超过500个字符',
            'anchorName.max'               => '主播名称不能超过100个字符',
            'anchorAvatar.max'             => '主播头像地址不能超过500个字符',
            'scheduledStartTime.date'      => '直播开始时间格式无效',
            'scheduledStartTime.after'     => '直播开始时间必须晚于当前时间',
            'scheduledEndTime.date'        => '直播结束时间格式无效',
            'scheduledEndTime.after'       => '直播结束时间必须晚于开始时间',
            'status.in'                    => '状态值无效',
        ];
    }
}
