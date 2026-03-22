<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Traits\LiveChapterUpsertRequestTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LiveChapterUpdateRequest extends FormRequest
{
    use LiveChapterUpsertRequestTrait;

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
        ], $this->liveChapterUpsertRules());
    }

    public function messages()
    {
        return array_merge([
            'chapterId.required' => '请选择章节。',
            'chapterId.integer' => '章节不存在。',
            'chapterId.exists' => '章节不存在或不属于当前课程。',
        ], $this->liveChapterUpsertMessages());
    }
}
