<?php

namespace App\Http\Requests\App;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 课表日视图日期参数验证
 */
class ScheduleDateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'date' => 'required|date_format:Y-m-d',
        ];
    }

    public function messages()
    {
        return [
            'date.required' => '日期不能为空。',
            'date.date_format' => '日期格式不正确，请使用 Y-m-d 格式。',
        ];
    }
}
