<?php

namespace App\Http\Requests\App;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 课表区间数据请求验证
 */
class ScheduleRangeRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'startDate' => 'required|date_format:Y-m-d',
            'endDate' => 'required|date_format:Y-m-d|after_or_equal:startDate',
        ];
    }

    public function messages()
    {
        return [
            'startDate.required' => '开始日期不能为空。',
            'startDate.date_format' => '开始日期格式不正确，请使用 Y-m-d 格式。',
            'endDate.required' => '结束日期不能为空。',
            'endDate.date_format' => '结束日期格式不正确，请使用 Y-m-d 格式。',
            'endDate.after_or_equal' => '结束日期不能早于开始日期。',
        ];
    }
}
