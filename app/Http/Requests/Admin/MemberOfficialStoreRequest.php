<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 后台新增官方会员请求校验。
 *
 * 职责：
 * 1. 约束官方会员新增接口的最小入参；
 * 2. 统一校验昵称、头像、官方标签与状态的格式；
 * 3. 防止无效参数进入服务层导致异常写库。
 */
class MemberOfficialStoreRequest extends FormRequest
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
     * 新增官方会员参数校验规则。
     *
     * @return array<string, string>
     */
    public function rules()
    {
        return [
            'nickname' => 'required|string|max:50',
            'avatar' => 'nullable|string|max:255',
            'officialLabel' => 'required|string|max:50',
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
            'nickname.required' => '昵称不能为空',
            'nickname.string' => '昵称格式无效',
            'nickname.max' => '昵称长度不能超过50个字符',
            'avatar.string' => '头像地址格式无效',
            'avatar.max' => '头像地址长度不能超过255个字符',
            'officialLabel.required' => '官方标签不能为空',
            'officialLabel.string' => '官方标签格式无效',
            'officialLabel.max' => '官方标签长度不能超过50个字符',
            'status.integer' => '状态值无效',
            'status.in' => '状态值无效',
        ];
    }
}
