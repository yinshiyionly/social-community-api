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
            // 章节基础信息
            'courseId' => 'required|integer|min:1',
            'chapterNo' => 'nullable|integer|min:0',
            'chapterTitle' => 'required|string|max:200',
            'chapterSubtitle' => 'nullable|string|max:300',
            'coverImage' => 'nullable|string|max:500',
            'brief' => 'nullable|string',
            'isFree' => 'nullable|integer|in:0,1',
            'isPreview' => 'nullable|integer|in:0,1',
            'unlockType' => 'nullable|integer|in:1,2,3',
            'unlockDays' => 'nullable|integer|min:0',
            'unlockDate' => 'nullable|date',
            'unlockTime' => 'nullable|date_format:H:i:s',
            'minLearnTime' => 'nullable|integer|min:0',
            'allowSkip' => 'nullable|integer|in:0,1',
            'allowSpeed' => 'nullable|integer|in:0,1',
            'sortOrder' => 'nullable|integer|min:0',
            // 视频内容
            'videoUrl' => 'nullable|string|max:500',
            'videoId' => 'nullable|string|max:100',
            'videoSource' => 'nullable|string|in:local,aliyun,tencent,volcengine',
            'duration' => 'nullable|integer|min:0',
            'width' => 'nullable|integer|min:0',
            'height' => 'nullable|integer|min:0',
            'fileSize' => 'nullable|integer|min:0',
            'videoCoverImage' => 'nullable|string|max:500',
            'qualityList' => 'nullable|array',
            'subtitles' => 'nullable|array',
            'attachments' => 'nullable|array',
            'allowDownload' => 'nullable|integer|in:0,1',
            'drmEnabled' => 'nullable|integer|in:0,1',
        ];
    }

    public function messages()
    {
        return [
            'courseId.required' => '课程ID不能为空',
            'courseId.integer' => '课程ID必须是整数',
            'chapterTitle.required' => '章节标题不能为空',
            'chapterTitle.max' => '章节标题不能超过200个字符',
            'chapterSubtitle.max' => '章节副标题不能超过300个字符',
            'coverImage.max' => '封面图地址不能超过500个字符',
            'isFree.in' => '是否免费值无效',
            'isPreview.in' => '是否先导课值无效',
            'unlockType.in' => '解锁类型无效',
            'unlockDays.integer' => '解锁天数必须是整数',
            'unlockDate.date' => '解锁日期格式无效',
            'videoSource.in' => '视频来源无效',
            'duration.integer' => '视频时长必须是整数',
        ];
    }
}
