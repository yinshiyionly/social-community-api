<?php

declare(strict_types=1);

namespace App\Http\Requests\Complaint\ComplaintEnterprise;

use App\Models\PublicRelation\ComplaintEnterprise;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateComplaintEnterpriseRequest extends FormRequest
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
            'material_id' => 'required|integer',
            'site_name' => 'required|string|max:100',
            'account_name' => 'required|string|max:100',
            'item_url' => 'required|array|min:1',
            'item_url.*.url' => 'required|string',
            'report_content' => 'required|string',
            'proof_type' => ['required', 'string', Rule::in(ComplaintEnterprise::PROOF_TYPE_OPTIONS)],
            'send_email' => 'required|string|max:100|exists:report_email,email',
            'channel_name' => 'required|string|max:100',

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
            'material_id.required' => '举报人不能为空',
            'material_id.integer' => '举报人必须是字符串',

            // site_name validation messages
            'site_name.required' => '举报网站名称不能为空',
            'site_name.string' => '举报网站名称必须是字符串',
            'site_name.max' => '举报网站名称不能超过100个字符',

            // account_name validation messages
            'account_name.required' => '举报账号名称不能为空',
            'account_name.string' => '举报账号名称必须是字符串',
            'account_name.max' => '举报账号名称不能超过100个字符',

            // item_url validation messages
            'item_url.required' => '详细举报网址不能为空',
            'item_url.array' => '详细举报网址必须是数组格式',
            'item_url.min' => '详细举报网址至少需要一条',
            'item_url.*.url.required' => '详细举报网址的URL不能为空',
            'item_url.*.url.string' => '详细举报网址的URL必须是字符串',

            // report_content validation messages
            'report_content.required' => '具体举报内容不能为空',
            'report_content.string' => '具体举报内容必须是字符串',

            // proof_type validation messages
            'proof_type.required' => '证据种类不能为空',
            'proof_type.string' => '证据种类必须是字符串',
            'proof_type.in' => '证据种类无效，请选择有效的证据种类',

            // send_email validation messages
            'send_email.required' => '发件箱不能为空',
            'send_email.string' => '发件箱必须是字符串',
            'send_email.max' => '发件箱不能超过100个字符',
            'send_email.exists' => '发件箱不存在，请选择有效的发件邮箱',

            // channel_name validation messages
            'channel_name.required' => '官方渠道不能为空',
            'channel_name.string' => '官方渠道必须是字符串',
            'channel_name.max' => '官方渠道不能超过100个字符',

            // report_state validation messages
            'report_state.integer' => '举报状态必须是整数',
            'report_state.in' => '举报状态值无效',

            // status validation messages
            'status.integer' => '状态必须是整数',
            'status.in' => '状态值无效',
        ];
    }
}
