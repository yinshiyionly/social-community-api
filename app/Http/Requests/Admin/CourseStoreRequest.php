<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CourseStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'categoryId' => 'required|integer|min:1',
            'courseTitle' => 'required|string|max:200',
            'courseSubtitle' => 'nullable|string|max:300',
            'payType' => 'required|integer|in:1,2,3',
            'playType' => 'required|integer|in:1,2,3,4',
            'scheduleType' => 'nullable|integer|in:1,2',
            'teacherName' => 'nullable|string|max:100',
            'classTeacherName' => 'nullable|string|max:100',
            'classTeacherQr' => 'nullable|string|max:500',
            'coverImage' => 'required|string|max:500',
            'itemImage' => 'required|string|max:500',
            'description' => 'required|string',
            'remark' => 'nullable|string',
            'originalPrice' => 'nullable|numeric|min:0|max:9999999.99',
            'currentPrice' => 'nullable|numeric|min:0|max:9999999.99',
            'isFree' => 'nullable|integer|in:0,1',
            'status' => 'nullable|integer|in:0,1,2',
            'publishTime' => 'nullable|date',
        ];
    }

    public function messages()
    {
        return [
            'categoryId.required' => '请选择课程分类',
            'categoryId.integer' => '分类ID必须是整数',
            'courseTitle.required' => '课程标题不能为空',
            'courseTitle.max' => '课程标题不能超过200个字符',
            'courseSubtitle.max' => '课程副标题不能超过300个字符',
            'payType.required' => '请选择付费类型',
            'payType.in' => '付费类型无效',
            'playType.required' => '请选择播放类型',
            'playType.in' => '播放类型无效',
            'scheduleType.in' => '排课类型无效',
            'teacherName.max' => '主讲老师名称不能超过100个字符',
            'classTeacherName.max' => '班主任名称不能超过100个字符',
            'classTeacherQr.max' => '班主任二维码地址不能超过500个字符',
            'coverImage.required' => '封面图地址不能为空',
            'coverImage.max' => '封面图地址不能超过500个字符',
            'itemImage.required' => '详情图地址不能为空',
            'itemImage.max' => '详情图地址不能超过500个字符',
            'description.required' => '课程描述不能为空',
            'originalPrice.numeric' => '原价必须是数字',
            'originalPrice.min' => '原价不能小于0',
            'currentPrice.numeric' => '现价必须是数字',
            'currentPrice.min' => '现价不能小于0',
            'status.in' => '状态值无效',
            'publishTime.date' => '发布时间格式无效',
        ];
    }
}
