<?php

namespace App\Http\Requests\Admin;

use App\Models\App\AppMemberBase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 后台系统消息发送请求校验。
 *
 * 约束目标：
 * 1. 保证 senderId 来自官方正常账号；
 * 2. 保证消息基础字段与跳转字段格式正确；
 * 3. 保证定向发送 memberIds 数量不超过 100。
 */
class MessageSystemSendRequest extends FormRequest
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
     * 发送接口参数校验规则。
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'senderId' => [
                'required',
                'integer',
                'min:1',
                Rule::exists('app_member_base', 'member_id')->where(function ($query) {
                    $query->where('is_official', AppMemberBase::OFFICIAL_YES)
                        ->where('status', AppMemberBase::STATUS_NORMAL)
                        ->whereNull('deleted_at');
                }),
            ],
            'title' => 'required|string|max:100',
            'content' => 'required|string',
            'coverUrl' => 'nullable|string|max:500',
            'linkType' => 'nullable|integer|in:1,2,3,4',
            'linkUrl' => 'nullable|string|max:500',
            'memberIds' => 'nullable|array|max:100',
            'memberIds.*' => 'integer|min:1',
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
            'senderId.required' => '请选择发送者',
            'senderId.integer' => '发送者ID格式不正确',
            'senderId.min' => '发送者ID必须大于0',
            'senderId.exists' => '发送者必须是官方正常账号',

            'title.required' => '消息标题不能为空',
            'title.max' => '消息标题长度不能超过100个字符',

            'content.required' => '消息内容不能为空',

            'coverUrl.max' => '封面地址长度不能超过500个字符',

            'linkType.integer' => '跳转类型格式不正确',
            'linkType.in' => '跳转类型仅支持1、2、3、4',

            'linkUrl.max' => '跳转链接长度不能超过500个字符',

            'memberIds.array' => 'memberIds 必须是数组',
            'memberIds.max' => '定向接收会员最多100个',
            'memberIds.*.integer' => 'memberIds 中的会员ID必须是整数',
            'memberIds.*.min' => 'memberIds 中的会员ID必须大于0',
        ];
    }
}
