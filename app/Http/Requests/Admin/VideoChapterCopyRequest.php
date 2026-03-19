<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 录播章节复制请求校验。
 *
 * 约束：
 * 1. 仅接收被复制章节的 `chapterId`；
 * 2. `chapterId` 必须是正整数；
 * 3. 章节归属与课型校验由 Service 统一处理，避免控制器重复分支。
 */
class VideoChapterCopyRequest extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    public function rules()
    {
        return [
            'chapterId' => 'required|integer|min:1',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'chapterId.required' => '章节ID不能为空',
            'chapterId.integer' => '章节ID必须是整数',
            'chapterId.min' => '章节ID必须大于0',
        ];
    }
}
