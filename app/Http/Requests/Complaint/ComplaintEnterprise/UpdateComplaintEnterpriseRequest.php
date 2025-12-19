<?php

declare(strict_types=1);

namespace App\Http\Requests\Complaint\ComplaintEnterprise;

use App\Models\PublicRelation\ComplaintEnterprise;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateComplaintEnterpriseRequest extends FormRequest
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
            // ID is required for update
            'id' => 'required|integer|exists:complaint_enterprise,id',

            // Optional fields (same as create but nullable)
            'material_id' => 'required|integer',
            'site_name' => 'nullable|string|max:100',
            'account_name' => 'nullable|string|max:100',
            // item_url array validation (JSON format: [{"url": "xxx"}, {"url": "yyy"}])
            'item_url' => 'nullable|array',
            'item_url.*.url' => 'required_with:item_url|string',
            'report_content' => 'nullable|string',
            'proof_type' => ['nullable', 'string', Rule::in(ComplaintEnterprise::PROOF_TYPE_OPTIONS)],
            'send_email' => 'nullable|string|max:100|exists:report_email,email',
            'channel_name' => 'nullable|string|max:100',

            // Enterprise material array validation
            'enterprise_material' => 'nullable|array',
            'enterprise_material.*.name' => 'required_with:enterprise_material|string',
            'enterprise_material.*.url' => 'required_with:enterprise_material|string',

            // Contact material array validation
            'contact_material' => 'nullable|array',
            'contact_material.*.name' => 'required_with:contact_material|string',
            'contact_material.*.url' => 'required_with:contact_material|string',

            // Report material array validation
            'report_material' => 'nullable|array',
            'report_material.*.name' => 'required_with:report_material|string',
            'report_material.*.url' => 'required_with:report_material|string',

            // Proof material array validation
            'proof_material' => 'nullable|array',
            'proof_material.*.name' => 'required_with:proof_material|string',
            'proof_material.*.url' => 'required_with:proof_material|string',

            // Optional fields
            'report_state' => ['nullable', 'integer', Rule::in(array_keys(ComplaintEnterprise::REPORT_STATE_LABELS))],
            'status' => ['nullable', 'integer', Rule::in([ComplaintEnterprise::STATUS_ENABLED, ComplaintEnterprise::STATUS_DISABLED])],
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
            // id validation messages
            'id.required' => 'ID不能为空',
            'id.integer' => 'ID必须是整数',
            'id.exists' => '记录不存在',

            'material_id.required' => '举报人不能为空',
            'material_id.integer' => '举报人错误',

            // site_name validation messages
            'site_name.string' => '举报网站名称必须是字符串',
            'site_name.max' => '举报网站名称不能超过100个字符',

            // account_name validation messages
            'account_name.string' => '举报账号名称必须是字符串',
            'account_name.max' => '举报账号名称不能超过100个字符',

            // item_url validation messages
            'item_url.array' => '详细举报网址必须是数组格式',
            'item_url.*.url.required_with' => '详细举报网址的URL不能为空',
            'item_url.*.url.string' => '详细举报网址的URL必须是字符串',

            // report_content validation messages
            'report_content.string' => '具体举报内容必须是字符串',

            // proof_type validation messages
            'proof_type.string' => '证据种类必须是字符串',
            'proof_type.in' => '证据种类无效，请选择有效的证据种类',

            // send_email validation messages
            'send_email.string' => '发件箱必须是字符串',
            'send_email.max' => '发件箱不能超过100个字符',
            'send_email.exists' => '发件箱不存在，请选择有效的发件邮箱',

            // channel_name validation messages
            'channel_name.string' => '官方渠道必须是字符串',
            'channel_name.max' => '官方渠道不能超过100个字符',

            // Enterprise material validation messages
            'enterprise_material.array' => '企业材料必须是数组',
            'enterprise_material.*.name.required_with' => '企业材料的文件名称不能为空',
            'enterprise_material.*.name.string' => '企业材料的文件名称必须是字符串',
            'enterprise_material.*.url.required_with' => '企业材料的文件地址不能为空',
            'enterprise_material.*.url.string' => '企业材料的文件地址必须是字符串',

            // Contact material validation messages
            'contact_material.array' => '联系人材料必须是数组',
            'contact_material.*.name.required_with' => '联系人材料的文件名称不能为空',
            'contact_material.*.name.string' => '联系人材料的文件名称必须是字符串',
            'contact_material.*.url.required_with' => '联系人材料的文件地址不能为空',
            'contact_material.*.url.string' => '联系人材料的文件地址必须是字符串',

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

            // report_state validation messages
            'report_state.integer' => '举报状态必须是整数',
            'report_state.in' => '举报状态值无效',

            // status validation messages
            'status.integer' => '状态必须是整数',
            'status.in' => '状态值无效',
        ];
    }
}
