<?php

declare(strict_types=1);

namespace App\Http\Requests\Complaint\ComplaintEnterprise;

use App\Models\Mail\ReportEmail;
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
            'id' => ['required', 'integer', function ($attr, $value, $fail) {
                $exists = ComplaintEnterprise::query()
                    ->where('id', $value)
                    ->exists();
                if (!$exists) {
                    $fail('记录不存在');
                }
            }],
            // Optional fields (same as create but nullable)
            'material_id' => 'required|integer',
            'site_name' => 'required|string|max:100',
            'account_name' => 'required|string|max:100',
            // item_url array validation (JSON format: [{"url": "xxx"}, {"url": "yyy"}])
            'item_url' => 'required|array',
            'item_url.*.url' => 'required_with:item_url|string',
            'report_content' => 'required|string',
            'proof_type' => 'required|array|min:1',
            'proof_type.*' => ['required', 'string', Rule::in(ComplaintEnterprise::PROOF_TYPE_OPTIONS)],
            'email_config_id' => ['required', 'integer', function ($attr, $value, $fail) {
                $exists = ReportEmail::query()
                    ->where('id', $value)
                    ->exists();
                if (!$exists) {
                    $fail('发件箱不存在，请选择有效的发件邮箱');
                }
            }],
            'channel_name' => 'required|string|max:100',

            // Report material array validation
            'report_material' => 'required|array',
            'report_material.*.name' => 'required_with:report_material|string',
            'report_material.*.url' => 'required_with:report_material|string',

            // Proof material array validation
            'proof_material' => 'required|array',
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
            'proof_type.required' => '证据种类不能为空',
            'proof_type.array' => '证据种类必须是数组格式',
            'proof_type.min' => '证据种类至少需要选择一项',
            'proof_type.*.required' => '证据种类选项不能为空',
            'proof_type.*.string' => '证据种类选项必须是字符串',
            'proof_type.*.in' => '证据种类选项无效，请选择有效的证据种类',

            // email_config_id validation messages
            'email_config_id.required' => '发件箱不能为空',
            'email_config_id.integer' => '发件箱错误',
            'email_config_id.exists' => '发件箱不存在，请选择有效的发件邮箱',

            // channel_name validation messages
            'channel_name.string' => '官方渠道必须是字符串',
            'channel_name.max' => '官方渠道不能超过100个字符',

            // Report material validation messages
            'report_material.required' => '举报材料不能为空',
            'report_material.array' => '举报材料必须是数组',
            'report_material.*.name.required_with' => '举报材料的文件名称不能为空',
            'report_material.*.name.string' => '举报材料的文件名称必须是字符串',
            'report_material.*.url.required_with' => '举报材料的文件地址不能为空',
            'report_material.*.url.string' => '举报材料的文件地址必须是字符串',

            // Proof material validation messages
            'proof_material.required' => '证据材料不能为空',
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
