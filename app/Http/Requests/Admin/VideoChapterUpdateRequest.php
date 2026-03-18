<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Traits\VideoChapterUpsertRequestTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VideoChapterUpdateRequest extends FormRequest
{
    use VideoChapterUpsertRequestTrait;

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return array_merge([
            'chapterId' => [
                'required',
                'integer',
                Rule::exists('app_course_chapter', 'chapter_id')
                    ->where('course_id', $this->input('courseId'))
                    ->whereNull('deleted_at'),
            ],
        ], $this->videoChapterUpsertRules());
    }

    public function messages()
    {
        return array_merge([
            'chapterId.required' => '章节ID不能为空。',
            'chapterId.integer' => '章节ID必须是整数。',
            'chapterId.exists' => '章节不存在或不属于当前课程。',
        ], $this->videoChapterUpsertMessages());
    }
}
