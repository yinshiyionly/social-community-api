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
            'roomCover'          => 'nullable|string|max:500',
            'roomIntro'          => 'nullable|string|max:5000',
            'anchorName'         => 'nullable|string|max:100',
            'anchorAvatar'       => 'nullable|string|max:500',
            'scheduledStartTime' => 'nullable|date',
            'scheduledEndTime'   => 'nullable|date|after:scheduledStartTime',
            'liveDuration'       => 'nullable|integer|min:0',
            'allowChat'          => 'nullable|integer|in:0,1',
            'allowGift'          => 'nullable|integer|in:0,1',
            'allowLike'          => 'nullable|integer|in:0,1',
            'password'           => 'nullable|string|max:100',
            'extConfig'          => 'nullable|array',
            'status'             => 'nullable|integer|in:0,1',
        ];

        if ($liveType === 2) {
            // 伪直播：videoUrl、scheduledStartTime、scheduledEndTime 必填
            $rules['videoUrl']            = 'required|string|max:500';
            $rules['scheduledStartTime']  = 'required|date|after:now';
            $rules['scheduledEndTime']    = 'required|date|after:scheduledStartTime';
        } else {
            // 真实直播/主播模式：videoUrl 可选
            $rules['videoUrl']       = 'nullable|string|max:500';
            $rules['livePlatform']   = 'nullable|string|in:custom,baijiayun,aliyun,tencent,agora';
            $rules['pushUrl']        = 'nullable|string|max:500';
            $rules['pullUrl']        = 'nullable|string|max:500';
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'roomTitle.required'           => '直播间标题不能为空',
            'roomTitle.max'                => '直播间标题不能超过200个字符',
            'liveType.required'            => '直播类型不能为空',
            'liveType.in'                  => '直播类型值无效',
            'roomCover.max'                => '封面地址不能超过500个字符',
            'roomIntro.max'                => '简介不能超过5000个字符',
            'anchorName.max'               => '主播名称不能超过100个字符',
            'anchorAvatar.max'             => '主播头像地址不能超过500个字符',
            'scheduledStartTime.date'      => '计划开始时间格式无效',
            'scheduledStartTime.after'     => '计划开始时间必须晚于当前时间',
            'scheduledEndTime.date'        => '计划结束时间格式无效',
            'scheduledEndTime.after'       => '计划结束时间必须晚于开始时间',
            'videoUrl.required'            => '伪直播视频地址不能为空',
            'videoUrl.max'                 => '视频地址不能超过500个字符',
            'livePlatform.in'              => '直播平台值无效',
            'pushUrl.max'                  => '推流地址不能超过500个字符',
            'pullUrl.max'                  => '拉流地址不能超过500个字符',
            'liveDuration.integer'         => '预计时长必须是整数',
            'liveDuration.min'             => '预计时长不能小于0',
            'allowChat.in'                 => '允许聊天值无效',
            'allowGift.in'                 => '允许送礼值无效',
            'allowLike.in'                 => '允许点赞值无效',
            'password.max'                 => '密码不能超过100个字符',
            'status.in'                    => '状态值无效',
        ];
    }
}
