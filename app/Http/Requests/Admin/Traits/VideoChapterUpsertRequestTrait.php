<?php

namespace App\Http\Requests\Admin\Traits;

use App\Models\App\AppCourseBase;
use App\Models\App\AppCourseChapter;
use Illuminate\Validation\Rule;

/**
 * 录播课章节创建/更新请求公共校验规则。
 *
 * 约束：
 * 1. 创建与更新复用同一套必填字段，避免规则漂移；
 * 2. 强校验 courseId 必须为录播课（play_type=录播课）；
 * 3. 按 unlockType 触发条件必填，减少前后端联调歧义。
 */
trait VideoChapterUpsertRequestTrait
{
    /**
     * 录播章节 upsert 公共规则。
     *
     * @return array<string, mixed>
     */
    protected function videoChapterUpsertRules(): array
    {
        return [
            'courseId' => [
                'required',
                'integer',
                Rule::exists('app_course_base', 'course_id')
                    ->where('play_type', AppCourseBase::PLAY_TYPE_VIDEO)
                    ->whereNull('deleted_at'),
            ],
            'chapterTitle' => 'required|string|max:200',
            'chapterSubtitle' => 'required|string|max:300',
            'coverImage' => 'required|string|max:500',
            'videoId' => [
                'required',
                'integer',
                Rule::exists('app_video_system', 'video_id')->whereNull('deleted_at'),
            ],
            'isFree' => 'required|integer|in:0,1',
            'unlockType' => 'required|integer|in:1,2,3',
            'unlockDays' => 'nullable|integer|min:1|required_if:unlockType,' . AppCourseChapter::UNLOCK_TYPE_DAYS,
            'unlockDate' => 'nullable|date|required_if:unlockType,' . AppCourseChapter::UNLOCK_TYPE_DATE,
            'chapterStartTime' => 'required|date',
            'chapterEndTime' => 'required|date|after:chapterStartTime',
            'status' => 'required|integer|in:0,1,2',
        ];
    }

    /**
     * 录播章节 upsert 公共错误文案。
     *
     * @return array<string, string>
     */
    protected function videoChapterUpsertMessages(): array
    {
        return [
            'courseId.required' => '请选择录播课程。',
            'courseId.integer' => '课程ID必须是整数。',
            'courseId.exists' => '课程不存在或不是录播课。',
            'chapterTitle.required' => '章节标题不能为空。',
            'chapterTitle.max' => '章节标题不能超过200个字符。',
            'chapterSubtitle.required' => '章节副标题不能为空。',
            'chapterSubtitle.max' => '章节副标题不能超过300个字符。',
            'coverImage.required' => '章节封面不能为空。',
            'coverImage.max' => '章节封面地址不能超过500个字符。',
            'videoId.required' => '请选择系统视频。',
            'videoId.integer' => '视频ID必须是整数。',
            'videoId.exists' => '视频不存在或已删除。',
            'isFree.required' => '请选择是否免费。',
            'isFree.in' => '是否免费值无效。',
            'unlockType.required' => '请选择解锁类型。',
            'unlockType.in' => '解锁类型值无效。',
            'unlockDays.required_if' => '按天数解锁时，解锁天数不能为空。',
            'unlockDays.integer' => '解锁天数必须是整数。',
            'unlockDays.min' => '解锁天数必须大于等于1。',
            'unlockDate.required_if' => '按日期解锁时，解锁日期不能为空。',
            'unlockDate.date' => '解锁日期格式不正确。',
            'chapterStartTime.required' => '章节开始时间不能为空。',
            'chapterStartTime.date' => '章节开始时间格式不正确。',
            'chapterEndTime.required' => '章节结束时间不能为空。',
            'chapterEndTime.date' => '章节结束时间格式不正确。',
            'chapterEndTime.after' => '章节结束时间必须晚于开始时间。',
            'status.required' => '请选择章节状态。',
            'status.in' => '章节状态值无效。',
        ];
    }
}
