<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 后台更新官方会员请求校验。
 *
 * 职责：
 * 1. 校验更新目标 memberId 的基本合法性；
 * 2. 约束可更新字段格式，避免无效数据覆盖线上账号；
 * 3. 强制“至少更新一个字段”，防止空请求造成无意义写操作。
 */
class MemberOfficialUpdateRequest extends FormRequest
{
    /**
     * 请求鉴权。
     *
     * 后台权限由路由中间件统一处理，请求层默认放行。
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * 更新官方会员参数校验规则。
     *
     * @return array<string, string>
     */
    public function rules()
    {
        return [
            'memberId' => 'required|integer|min:1',
            'nickname' => 'nullable|string|max:50',
            'avatar' => 'nullable|string|max:255',
            'officialLabel' => 'nullable|string|max:50',
            'status' => 'nullable|integer|in:1,2',
        ];
    }

    /**
     * 自定义错误文案。
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'memberId.required' => '会员ID不能为空',
            'memberId.integer' => '会员ID格式无效',
            'memberId.min' => '会员ID无效',
            'nickname.string' => '昵称格式无效',
            'nickname.max' => '昵称长度不能超过50个字符',
            'avatar.string' => '头像地址格式无效',
            'avatar.max' => '头像地址长度不能超过255个字符',
            'officialLabel.string' => '官方标签格式无效',
            'officialLabel.max' => '官方标签长度不能超过50个字符',
            'status.integer' => '状态值无效',
            'status.in' => '状态值无效',
        ];
    }

    /**
     * 增加跨字段校验，确保不是空更新请求。
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $updatableFields = ['nickname', 'avatar', 'officialLabel', 'status'];
            $hasUpdatableField = false;

            foreach ($updatableFields as $field) {
                if ($this->exists($field)) {
                    $hasUpdatableField = true;
                    break;
                }
            }

            // 未传任何可更新字段时直接拦截，避免无效请求落到服务层。
            if (!$hasUpdatableField) {
                $validator->errors()->add('memberId', '至少传入一个可更新字段');
            }
        });
    }
}
