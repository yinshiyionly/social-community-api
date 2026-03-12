<?php

namespace App\Http\Requests\Admin\Traits;

use App\Models\App\AppMemberBase;
use Illuminate\Validation\Rule;

/**
 * 后台发帖请求公共校验 Trait。
 *
 * 职责：
 * 1. 统一提供后台发帖必填的 memberId 校验规则；
 * 2. 约束发帖人必须为“官方 + 正常状态 + 未软删”会员；
 * 3. 复用错误文案，避免多个 Request 出现不一致提示。
 */
trait AdminPostStoreRequestTrait
{
    /**
     * 后台发帖的发帖人校验规则。
     *
     * 约束：
     * - memberId 必须存在；
     * - memberId 必须对应 app_member_base 中官方正常账号；
     * - 软删账号不允许继续作为后台发帖身份。
     *
     * @return array<string, mixed>
     */
    protected function memberRules(): array
    {
        return [
            'memberId' => [
                'required',
                'integer',
                'min:1',
                Rule::exists('app_member_base', 'member_id')->where(function ($query) {
                    $query->where('is_official', AppMemberBase::OFFICIAL_YES)
                        ->where('status', AppMemberBase::STATUS_NORMAL)
                        ->whereNull('deleted_at');
                }),
            ],
        ];
    }

    /**
     * 后台发帖的发帖人校验错误文案。
     *
     * @return array<string, string>
     */
    protected function memberMessages(): array
    {
        return [
            'memberId.required' => '请选择发帖人',
            'memberId.integer' => '发帖人ID格式不正确',
            'memberId.min' => '发帖人ID必须大于0',
            'memberId.exists' => '发帖人必须是官方正常账号',
        ];
    }
}
