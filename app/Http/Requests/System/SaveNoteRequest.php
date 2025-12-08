<?php

namespace App\Http\Requests\System;

use Illuminate\Foundation\Http\FormRequest;

class SaveNoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'userId' => 'required|max:50',
            'note' => 'required|string|max:500',
//            'pageType' => 'required|string|in:operations,operationsTool,repeatwx',
            'pageType' => 'required|string',
            'operatorId' => 'required|string|max:50',
            'operatorName' => 'required|string|max:50'
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'userId.required' => '用户ID不能为空',
            'userId.max' => '用户ID不能超过50个字符',
            'note.required' => '备注内容不能为空',
            'note.max' => '备注内容不能超过500个字符',
            'pageType.required' => '页面类型不能为空',
            'pageType.in' => '页面类型必须是operations、operationsTool或repeatwx',
            'operatorId.required' => '操作员ID不能为空',
            'operatorId.max' => '操作员ID不能超过50个字符',
            'operatorName.required' => '操作员姓名不能为空',
            'operatorName.max' => '操作员姓名不能超过50个字符'
        ];
    }
}
