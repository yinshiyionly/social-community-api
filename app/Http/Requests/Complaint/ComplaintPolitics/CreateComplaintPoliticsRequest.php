<?php

declare(strict_types=1);

namespace App\Http\Requests\Complaint\ComplaintPolitics;

use App\Models\Mail\ReportEmail;
use App\Models\PublicRelation\ComplaintPolitics;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * 创建政治类投诉请求验证类
 *
 * 实现以下验证逻辑：
 * - report_platform 枚举验证
 * - 条件必填字段验证（根据 report_platform 动态验证）
 * - account_platform_name 条件必填验证
 * - account_nature 根据 account_platform 动态验证
 * - report_sub_type 危害小类枚举验证
 * - email_config_id 关联 report_email 表验证
 * - URL 字段数组格式验证
 */
class CreateComplaintPoliticsRequest extends FormRequest
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
            // 被举报平台 - 必填，枚举验证
            'report_platform' => ['required', 'string', Rule::in(ComplaintPolitics::REPORT_PLATFORM_OPTIONS)],

            // 危害小类 - 必填，枚举验证
            'report_sub_type' => ['required', 'string', Rule::in(ComplaintPolitics::REPORT_SUB_TYPE_OPTIONS)],

            // ==================== 网站网页相关字段 ====================
            // 当 report_platform=网站网页 时必填
            'site_name' => 'required_if:report_platform,网站网页|nullable|string|max:100',
            'site_url' => 'required_if:report_platform,网站网页|nullable|array|min:1',
            'site_url.*.url' => 'required_with:site_url|string',

            // ==================== APP相关字段 ====================
            // 当 report_platform=APP 时必填
            'app_name' => 'required_if:report_platform,APP|nullable|string|max:100',
            'app_location' => [
                'required_if:report_platform,APP',
                'nullable',
                'string',
                Rule::in(ComplaintPolitics::APP_LOCATION_OPTIONS),
            ],
            'app_url' => 'required_if:report_platform,APP|nullable|array|min:1',
            'app_url.*.url' => 'required_with:app_url|string',

            // ==================== 网络账号相关字段 ====================
            // 当 report_platform=网络账号 时必填
            'account_platform' => [
                'required_if:report_platform,网络账号',
                'nullable',
                'string',
                Rule::in(ComplaintPolitics::ACCOUNT_PLATFORM_OPTIONS),
            ],
            'account_nature' => 'nullable|string|max:50',
            'account_name' => 'required_if:report_platform,网络账号|nullable|string|max:100',
            'account_url' => 'nullable|array|min:1',
            'account_url.*.url' => 'required_with:account_url|string',
            // account_platform_name 在 withValidator 中进行条件验证

            // ==================== 通用必填字段 ====================
            'material_id' => 'required|integer',
            'email_config_id' => ['required', 'integer', function ($attr, $value, $fail) {
                $exists = ReportEmail::query()
                    ->where('id', $value)
                    ->exists();
                if (!$exists) {
                    $fail('发件箱不存在，请选择有效的发件邮箱');
                }
            }],
            'channel_name' => 'required|string|max:50',

            // ==================== 可选字段 ====================
            'report_material' => 'required|array',
            'report_material.*.name' => 'required_with:report_material|string',
            'report_material.*.url' => 'required_with:report_material|string',
            'report_content' => 'required|string',
            'report_state' => ['nullable', 'integer', Rule::in(array_keys(ComplaintPolitics::REPORT_STATE_LABELS))],
            'status' => ['nullable', 'integer', Rule::in([ComplaintPolitics::STATUS_ENABLED, ComplaintPolitics::STATUS_DISABLED])],
        ];
    }

    /**
     * Configure the validator instance.
     * 添加自定义验证逻辑
     *
     * @param Validator $validator
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        // 条件验证：当 report_platform=网络账号 且 account_platform 需要填写平台名称时
        $validator->sometimes('account_platform_name', 'required|string|max:100', function ($input) {
            return $input->report_platform === ComplaintPolitics::REPORT_PLATFORM_ACCOUNT
                && in_array($input->account_platform, ComplaintPolitics::ACCOUNT_PLATFORM_NEED_NAME, true);
        });

        // 条件验证：当 report_platform=网络账号 且 account_platform 不是微信/QQ时，account_url 必填
        $validator->sometimes('account_url', 'required|array|min:1', function ($input) {
            return $input->report_platform === ComplaintPolitics::REPORT_PLATFORM_ACCOUNT
                && !in_array($input->account_platform, ['微信', 'QQ'], true);
        });

        // 条件验证：当 report_platform=网络账号 且 account_platform 是微信/QQ/微博时，account_nature 必填
        $validator->sometimes('account_nature', 'required|string|max:50', function ($input) {
            return $input->report_platform === ComplaintPolitics::REPORT_PLATFORM_ACCOUNT
                && in_array($input->account_platform, ['微信', 'QQ', '微博'], true);
        });

        // 验证 account_nature 根据 account_platform 动态变化
        $validator->after(function (Validator $validator) {
            $this->validateAccountNature($validator);
        });
    }

    /**
     * 验证账号性质是否符合对应账号平台的有效值
     * 只有当 report_platform=网络账号 且 account_platform=微信/QQ/微博 时才验证
     *
     * @param Validator $validator
     * @return void
     */
    protected function validateAccountNature(Validator $validator): void
    {
        $reportPlatform = $this->input('report_platform');
        $accountPlatform = $this->input('account_platform');
        $accountNature = $this->input('account_nature');

        // 只有当 report_platform=网络账号 且 account_platform=微信/QQ/微博 时才验证 account_nature
        if ($reportPlatform !== ComplaintPolitics::REPORT_PLATFORM_ACCOUNT) {
            return;
        }

        // 只有微信/QQ/微博需要验证账号性质
        if (!in_array($accountPlatform, ['微信', 'QQ', '微博'], true)) {
            return;
        }

        // 获取该账号平台对应的有效账号性质选项
        $validNatures = ComplaintPolitics::getAccountNatureOptions($accountPlatform);

        // 如果该平台有账号性质要求，则验证
        if (!empty($validNatures)) {
            if (empty($accountNature)) {
                $validator->errors()->add('account_nature', '当账号平台为' . $accountPlatform . '时，账号性质不能为空');
                return;
            }

            if (!in_array($accountNature, $validNatures, true)) {
                $validator->errors()->add(
                    'account_nature',
                    '账号性质无效，当账号平台为' . $accountPlatform . '时，请选择：' . implode('、', $validNatures)
                );
            }
        }
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            // ==================== 被举报平台验证消息 ====================
            'report_platform.required' => '被举报平台不能为空',
            'report_platform.string' => '被举报平台必须是字符串',
            'report_platform.in' => '被举报平台无效，请选择有效的被举报平台',

            // ==================== 危害小类验证消息 ====================
            'report_sub_type.required' => '危害小类不能为空',
            'report_sub_type.string' => '危害小类必须是字符串',
            'report_sub_type.in' => '危害小类无效，请选择有效的危害小类',

            // ==================== 网站网页相关字段验证消息 ====================
            'site_name.required_if' => '当被举报平台为网站网页时，网站名称不能为空',
            'site_name.string' => '网站名称必须是字符串',
            'site_name.max' => '网站名称不能超过100个字符',
            'site_url.required_if' => '当被举报平台为网站网页时，网站网址不能为空',
            'site_url.array' => '网站网址必须是数组格式',
            'site_url.min' => '网站网址至少需要一条',
            'site_url.*.url.required_with' => '网站网址的URL不能为空',
            'site_url.*.url.string' => '网站网址的URL必须是字符串',

            // ==================== APP相关字段验证消息 ====================
            'app_name.required_if' => '当被举报平台为APP时，APP名称不能为空',
            'app_name.string' => 'APP名称必须是字符串',
            'app_name.max' => 'APP名称不能超过100个字符',
            'app_location.required_if' => '当被举报平台为APP时，APP定位不能为空',
            'app_location.string' => 'APP定位必须是字符串',
            'app_location.in' => 'APP定位无效，请选择有效的APP定位',
            'app_url.required_if' => '当被举报平台为APP时，APP网址不能为空',
            'app_url.array' => 'APP网址必须是数组格式',
            'app_url.min' => 'APP网址至少需要一条',
            'app_url.*.url.required_with' => 'APP网址的URL不能为空',
            'app_url.*.url.string' => 'APP网址的URL必须是字符串',

            // ==================== 网络账号相关字段验证消息 ====================
            'account_platform.required_if' => '当被举报平台为网络账号时，账号平台不能为空',
            'account_platform.string' => '账号平台必须是字符串',
            'account_platform.in' => '账号平台无效，请选择有效的账号平台',
            'account_nature.required' => '当账号平台为微信/QQ/微博时，账号性质不能为空',
            'account_nature.string' => '账号性质必须是字符串',
            'account_nature.max' => '账号性质不能超过50个字符',
            'account_name.required_if' => '当被举报平台为网络账号时，账号名称不能为空',
            'account_name.string' => '账号名称必须是字符串',
            'account_name.max' => '账号名称不能超过100个字符',
            'account_url.required' => '当被举报平台为网络账号且账号平台不是微信/QQ时，账号网址不能为空',
            'account_url.array' => '账号网址必须是数组格式',
            'account_url.min' => '账号网址至少需要一条',
            'account_url.*.url.required_with' => '账号网址的URL不能为空',
            'account_url.*.url.string' => '账号网址的URL必须是字符串',
            'account_platform_name.required' => '当账号平台为博客/直播平台/论坛社区/网盘/音频/其他时，账号平台名称不能为空',
            'account_platform_name.string' => '账号平台名称必须是字符串',
            'account_platform_name.max' => '账号平台名称不能超过100个字符',

            // ==================== 通用字段验证消息 ====================
            'material_id.required' => '举报人不能为空',
            'material_id.integer' => '举报人ID必须是整数',
            'email_config_id.required' => '发件邮箱不能为空',
            'email_config_id.integer' => '发件邮箱错误',
            'email_config_id.exists' => '发件邮箱不存在，请选择有效的发件邮箱',
            'channel_name.required' => '官方渠道不能为空',
            'channel_name.string' => '官方渠道必须是字符串',
            'channel_name.max' => '官方渠道不能超过50个字符',

            // ==================== 举报材料验证消息 ====================
            'report_material.required' => '举报材料不能为空',
            'report_material.array' => '举报材料必须是数组格式',
            'report_material.*.name.required_with' => '举报材料的文件名称不能为空',
            'report_material.*.name.string' => '举报材料的文件名称必须是字符串',
            'report_material.*.url.required_with' => '举报材料的文件地址不能为空',
            'report_material.*.url.string' => '举报材料的文件地址必须是字符串',

            // ==================== 其他字段验证消息 ====================
            'report_content.required' => '举报内容不能为空',
            'report_content.string' => '举报内容必须是字符串',
            'report_state.integer' => '举报状态必须是整数',
            'report_state.in' => '举报状态值无效',
            'status.integer' => '状态必须是整数',
            'status.in' => '状态值无效',
        ];
    }
}
