<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ChapterStatusRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'chapterId' => 'required|integer|min:1',
            'status' => 'required|integer|in:0,1,2',
        ];
    }

    public function messages()
    {
        return [
            'chapterId.required' => '章节ID不能为空',
            'chapterId.integer' => '章节ID必须是整数',
            'status.required' => '状态不能为空',
            'status.in' => '状态值无效',
        ];
    }
}
