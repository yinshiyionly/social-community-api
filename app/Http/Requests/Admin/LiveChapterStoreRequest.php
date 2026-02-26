<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class LiveChapterStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'courseId'        => 'required|integer|min:1',
            'chapterTitle'   => 'required|string|max:200',
            'liveStartTime'  => 'required|date|after:now',
            'liveEndTime'    => 'required|date|after:liveStartTime',
            'chapterNo'      => 'nullable|integer|min:0',
            'chapterSubtitle'=> 'nullable|string|max:200',
            'coverImage'     => 'nullable|string|max:500',
            'brief'          => 'nullable|string|max:2000',
            'sortOrder'      => 'nullable|integer|min:0',
            'liveCover'      => 'nullable|string|max:500',
            'liveDuration'   => 'nullable|integer|min:0',
            'allowChat'      => 'nullable|integer|in:0,1',
            'allowGift'      => 'nullable|integer|in:0,1',
            'attachments'    => 'nullable|array',
        ];
    }

    public function messages()
    {
        return [
            'courseId.required'       => '课程ID不能为空',
            'courseId.integer'        => '课程ID必须是整数',
            'courseId.min'            => '课程ID无效',
            'chapterTitle.required'   => '章节标题不能为空',
            'chapterTitle.max'        => '章节标题不能超过200个字符',
            'liveStartTime.required'  => '直播开始时间不能为空',
            'liveStartTime.date'      => '直播开始时间格式无效',
            'liveStartTime.after'     => '直播开始时间必须晚于当前时间',
            'liveEndTime.required'    => '直播结束时间不能为空',
            'liveEndTime.date'        => '直播结束时间格式无效',
            'liveEndTime.after'       => '直播结束时间必须晚于直播开始时间',
            'chapterNo.integer'       => '章节序号必须是整数',
            'chapterSubtitle.max'     => '章节副标题不能超过200个字符',
            'coverImage.max'          => '封面图地址不能超过500个字符',
            'brief.max'               => '简介不能超过2000个字符',
            'sortOrder.integer'       => '排序值必须是整数',
            'sortOrder.min'           => '排序值不能小于0',
            'liveCover.max'           => '直播封面地址不能超过500个字符',
            'liveDuration.integer'    => '预计时长必须是整数',
            'liveDuration.min'        => '预计时长不能小于0',
            'allowChat.in'            => '允许聊天值无效',
            'allowGift.in'            => '允许送礼值无效',
            'attachments.array'       => '直播资料格式无效',
        ];
    }
}
