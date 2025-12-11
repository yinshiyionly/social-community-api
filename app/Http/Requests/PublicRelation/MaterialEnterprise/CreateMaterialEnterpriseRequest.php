<?php

declare(strict_types=1);

namespace App\Http\Requests\PublicRelation\MaterialEnterprise;

use App\Models\PublicRelation\MaterialEnterprise;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateMaterialEnterpriseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            // Required fields
            'name' => 'required|string|max:100',
            'contact_name' => 'required|string|max:20',
            'contact_phone' => ['required', 'string', 'regex:/^1[3-9]\d{9}$/'],

            // Enterprise material array validation
            'enterprise_material' => 'nullable|array',
            'enterprise_material.*.name' => 'required_with:enterprise_material|string',
            'enterprise_material.*.url' => 'required_with:enterprise_material|string',

            // Report material array validation
            'report_material' => 'nullable|array',
            'report_material.*.name' => 'required_with:report_material|string',
            'report_material.*.url' => 'required_with:report_material|string',

            // Proof material array validation
            'proof_material' => 'nullable|array',
            'proof_material.*.name' => 'required_with:proof_material|string',
            'proof_material.*.url' => 'required_with:proof_material|string',

            // Enum field validations
            'type' => ['nullable', 'string', Rule::in(MaterialEnterprise::TYPE_OPTIONS)],
            'nature' => ['nullable', 'string', Rule::in(MaterialEnterprise::NATURE_OPTIONS)],
            'industry' => ['nullable', 'string', Rule::in(MaterialEnterprise::INDUSTRY_OPTIONS)],
            'contact_identity' => ['nullable', 'string', Rule::in(MaterialEnterprise::CONTACT_IDENTITY_OPTIONS)],

            // Email validation
            'contact_email' => 'nullable|email|max:50',

            // Status validation
            'status' => ['nullable', 'integer', Rule::in([MaterialEnterprise::STATUS_ENABLED, MaterialEnterprise::STATUS_DISABLED])],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            // Name validation messages
            'name.required' => '企业名称不能为空',
            'name.string' => '企业名称必须是字符串',
            'name.max' => '企业名称不能超过100个字符',

            // Contact name validation messages
            'contact_name.required' => '联系人姓名不能为空',
            'contact_name.string' => '联系人姓名必须是字符串',
            'contact_name.max' => '联系人姓名不能超过20个字符',

            // Contact phone validation messages
            'contact_phone.required' => '有效电话不能为空',
            'contact_phone.string' => '有效电话必须是字符串',
            'contact_phone.regex' => '有效电话格式不正确',

            // Enterprise material validation messages
            'enterprise_material.array' => '企业材料必须是数组',
            'enterprise_material.*.name.required_with' => '企业材料的文件名称不能为空',
            'enterprise_material.*.name.string' => '企业材料的文件名称必须是字符串',
            'enterprise_material.*.url.required_with' => '企业材料的文件地址不能为空',
            'enterprise_material.*.url.string' => '企业材料的文件地址必须是字符串',

            // Report material validation messages
            'report_material.array' => '举报材料必须是数组',
            'report_material.*.name.required_with' => '举报材料的文件名称不能为空',
            'report_material.*.name.string' => '举报材料的文件名称必须是字符串',
            'report_material.*.url.required_with' => '举报材料的文件地址不能为空',
            'report_material.*.url.string' => '举报材料的文件地址必须是字符串',

            // Proof material validation messages
            'proof_material.array' => '证据材料必须是数组',
            'proof_material.*.name.required_with' => '证据材料的文件名称不能为空',
            'proof_material.*.name.string' => '证据材料的文件名称必须是字符串',
            'proof_material.*.url.required_with' => '证据材料的文件地址不能为空',
            'proof_material.*.url.string' => '证据材料的文件地址必须是字符串',

            // Enum field validation messages
            'type.in' => '企业类型无效',
            'nature.in' => '企业性质无效',
            'industry.in' => '行业分类无效',
            'contact_identity.in' => '联系人身份无效',

            // Email validation messages
            'contact_email.email' => '电子邮件格式不正确',
            'contact_email.max' => '电子邮件不能超过50个字符',

            // Status validation messages
            'status.integer' => '状态必须是整数',
            'status.in' => '状态值无效',
        ];
    }
}
