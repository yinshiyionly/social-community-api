<?php

declare(strict_types=1);

namespace App\Http\Requests\Complaint\ComplaintDefamation;

use App\Models\PublicRelation\ComplaintDefamation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 更新诽谤类投诉请求验证类
 *
 * 实现以下验证逻辑：
 * - id 必填且存在于 complaint_defamation 表
 * - 其他字段改为可选验证（nullable）
 * - material_id 可选，整数
 * - site_name 可选，不超过100字符
 * - site_url 可选，有效的JSON数组格式 [{"url": "xxx"}]
 * - report_content 可选，字符串
 * - email_config_id 可选，存在于 report_email 表
 * - channel_name 可选，不超过100字符
 * - report_material 可选，数组格式，每项包含 name 和 url 字段
 */
class UpdateComplaintDefamationRequest extends FormRequest
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
            // ==================== 必填字段 ====================
            // ID - 必填，整数，存在于 complaint_defamation 表
            'id' => 'required|integer|exists:complaint_defamation,id',

            // ==================== 可选字段（更新时均为可选） ====================
            // 举报人资料ID - 可选，整数
            'material_id' => 'nullable|integer',

            // 网站名称 - 可选，字符串，最大100字符
            'site_name' => 'nullable|string|max:100',

            // 详细举报网址 - 可选，数组格式 [{"url": "xxx"}]
            'site_url' => 'nullable|array|min:1',
            'site_url.*.url' => 'required_with:site_url|string',

            // 具体举报内容 - 可选，字符串
            'report_content' => 'nullable|string',

            // 发件邮箱 - 可选，存在于 report_email 表
            'email_config_id' => 'required|integer|exists:report_email,id',

            // 渠道名称 - 可选，字符串，最大100字符
            'channel_name' => 'nullable|string|max:100',

            // 举报材料 - 可选，数组格式
            'report_material' => 'nullable|array',
            'report_material.*.name' => 'required_with:report_material|string',
            'report_material.*.url' => 'required_with:report_material|string',

            // 举报状态 - 可选，整数，枚举验证
            'report_state' => ['nullable', 'integer', Rule::in(array_keys(ComplaintDefamation::REPORT_STATE_LABELS))],

            // 状态 - 可选，整数，枚举验证
            'status' => ['nullable', 'integer', Rule::in([ComplaintDefamation::STATUS_ENABLED, ComplaintDefamation::STATUS_DISABLED])],
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
            // ==================== id 验证消息 ====================
            'id.required' => 'ID不能为空',
            'id.integer' => 'ID必须是整数',
            'id.exists' => '记录不存在',

            // ==================== material_id 验证消息 ====================
            'material_id.integer' => '举报人ID必须是整数',

            // ==================== site_name 验证消息 ====================
            'site_name.string' => '网站名称必须是字符串',
            'site_name.max' => '网站名称不能超过100个字符',

            // ==================== site_url 验证消息 ====================
            'site_url.array' => '详细举报网址必须是数组格式',
            'site_url.min' => '详细举报网址至少需要一条',
            'site_url.*.url.required_with' => '详细举报网址的URL不能为空',
            'site_url.*.url.string' => '详细举报网址的URL必须是字符串',

            // ==================== report_content 验证消息 ====================
            'report_content.string' => '具体举报内容必须是字符串',

            // ==================== email_config_id 验证消息 ====================
            'email_config_id.required' => '发件邮箱不能为空',
            'email_config_id.integer' => '发件邮箱错误',
            'email_config_id.exists' => '发件邮箱不存在，请选择有效的发件邮箱',

            // ==================== channel_name 验证消息 ====================
            'channel_name.string' => '官方渠道必须是字符串',
            'channel_name.max' => '官方渠道不能超过100个字符',

            // ==================== report_material 验证消息 ====================
            'report_material.array' => '举报材料必须是数组格式',
            'report_material.*.name.required_with' => '举报材料的文件名称不能为空',
            'report_material.*.name.string' => '举报材料的文件名称必须是字符串',
            'report_material.*.url.required_with' => '举报材料的文件地址不能为空',
            'report_material.*.url.string' => '举报材料的文件地址必须是字符串',

            // ==================== report_state 验证消息 ====================
            'report_state.integer' => '举报状态必须是整数',
            'report_state.in' => '举报状态值无效',

            // ==================== status 验证消息 ====================
            'status.integer' => '状态必须是整数',
            'status.in' => '状态值无效',
        ];
    }
}
