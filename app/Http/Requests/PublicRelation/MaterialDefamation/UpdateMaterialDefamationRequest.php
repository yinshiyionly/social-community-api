<?php

declare(strict_types=1);

namespace App\Http\Requests\PublicRelation\MaterialDefamation;

use App\Models\PublicRelation\MaterialDefamation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 更新诽谤资料请求验证
 *
 * 验证更新诽谤资料时的请求参数
 */
class UpdateMaterialDefamationRequest extends FormRequest
{
    /**
     * 确定用户是否有权发出此请求
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 获取适用于请求的验证规则
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            // 举报主体验证
            'report_subject' => ['nullable', 'string', 'max:50', Rule::in(MaterialDefamation::REPORT_SUBJECT_OPTIONS)],

            // 从业类别验证
            'occupation_category' => ['nullable', 'string', 'max:50', Rule::in(MaterialDefamation::OCCUPATION_OPTIONS)],

            // 单位名称验证
            'enterprise_name' => 'nullable|string|max:100',

            // 有效电话验证
            'contact_phone' => ['nullable', 'string', 'regex:/^1[3-9]\d{9}$/'],

            // 电子邮件验证
            'contact_email' => 'nullable|email|max:50',

            // 真实姓名验证
            'real_name' => 'nullable|string|max:50',

            // 举报材料数组验证
            'report_material' => 'nullable|array',
            'report_material.*.name' => 'required_with:report_material|string',
            'report_material.*.url' => 'required_with:report_material|string',

            // 状态验证
            'status' => ['nullable', 'integer', Rule::in([MaterialDefamation::STATUS_ENABLED, MaterialDefamation::STATUS_DISABLED])],
        ];
    }

    /**
     * 获取验证错误的自定义消息
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            // 举报主体验证消息
            'report_subject.string' => '举报主体必须是字符串',
            'report_subject.max' => '举报主体不能超过50个字符',
            'report_subject.in' => '举报主体值无效',

            // 从业类别验证消息
            'occupation_category.string' => '从业类别必须是字符串',
            'occupation_category.max' => '从业类别不能超过50个字符',
            'occupation_category.in' => '从业类别值无效',

            // 单位名称验证消息
            'enterprise_name.string' => '单位名称必须是字符串',
            'enterprise_name.max' => '单位名称不能超过100个字符',

            // 有效电话验证消息
            'contact_phone.string' => '有效电话必须是字符串',
            'contact_phone.regex' => '有效电话格式不正确',

            // 电子邮件验证消息
            'contact_email.email' => '电子邮件格式不正确',
            'contact_email.max' => '电子邮件不能超过50个字符',

            // 真实姓名验证消息
            'real_name.string' => '真实姓名必须是字符串',
            'real_name.max' => '真实姓名不能超过50个字符',

            // 举报材料验证消息
            'report_material.array' => '举报材料必须是数组',
            'report_material.*.name.required_with' => '举报材料的文件名称不能为空',
            'report_material.*.name.string' => '举报材料的文件名称必须是字符串',
            'report_material.*.url.required_with' => '举报材料的文件地址不能为空',
            'report_material.*.url.string' => '举报材料的文件地址必须是字符串',

            // 状态验证消息
            'status.integer' => '状态必须是整数',
            'status.in' => '状态值无效',
        ];
    }
}
