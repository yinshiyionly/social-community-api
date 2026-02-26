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
            'chapterId'      => 'required|integer|min:1',
            'chapterTitle'   => 'nullable|string|max:200',
            'liveRoomId'     => 'nullable|string|max:50',
            'chapterNo'      => 'nullable|integer|min:0',
            'chapterSubtitle'=> 'nullable|string|max:200',
            'coverImage'     => 'nullable|string|max:500',
            'brief'          => 'nullable|string|max:2000',
            'sortOrder'      => 'nullable|integer|min:0',
            'liveCover'      => 'nullable|string|max:500',
            'liveDuration'   => 'nullable|integer|min:0',
            'liveStartTime'  => 'nullable|date',
            'liveEndTime'    => 'nullable|date|after:liveStartTime',
            'allowChat'      => 'nullable|integer|in:0,1',
            'allowGift'      => 'nullable|integer|in:0,1',
            'attachments'    => 'nullable|array',
        ];
    }

    public function messages()
    {
        return [
            'chapterId.required'      => '章节ID不能为空',
            'chapterId.integer'       => '章节ID必须是整数',
            'chapterId.min'           => '章节ID无效',
            'chapterTitle.max'        => '章节标题不能超过200个字符',
            'liveRoomId.string'       => '直播间ID必须是字符串',
            'liveRoomId.max'          => '直播间ID不能超过50个字符',
            'chapterSubtitle.max'     => '章节副标题不能超过200个字符',
            'coverImage.max'          => '封面图地址不能超过500个字符',
            'brief.max'               => '简介不能超过2000个字符',
            'sortOrder.integer'       => '排序值必须是整数',
            'sortOrder.min'           => '排序值不能小于0',
            'liveCover.max'           => '直播封面地址不能超过500个字符',
            'liveDuration.integer'    => '预计时长必须是整数',
            'liveDuration.min'        => '预计时长不能小于0',
            'liveStartTime.date'      => '直播开始时间格式无效',
            'liveEndTime.date'        => '直播结束时间格式无效',
            'liveEndTime.after'       => '直播结束时间必须晚于直播开始时间',
            'allowChat.in'            => '允许聊天值无效',
            'allowGift.in'            => '允许送礼值无效',
            'attachments.array'       => '直播资料格式无效',
        ];
    }
}
