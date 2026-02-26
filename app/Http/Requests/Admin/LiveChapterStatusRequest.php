<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class LiveChapterStatusRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'chapterId' => 'required|integer|min:1',
            'status'    => 'required|integer|in:1,2',
        ];
    }

    public function messages()
    {
        return [
            'chapterId.required' => '章节ID不能为空',
            'chapterId.integer'  => '章节ID必须是整数',
            'chapterId.min'      => '章节ID无效',
            'status.required'    => '状态不能为空',
            'status.integer'     => '状态必须是整数',
            'status.in'          => '状态值无效，仅支持上架(1)和下架(2)',
        ];
    }
}
