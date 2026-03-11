<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 广告内容批量排序请求验证。
 *
 * 约束说明：
 * 1. 同一批请求中的 adId 必须唯一，避免同条记录被重复更新导致结果不可预期；
 * 2. sortNum 仅允许非负整数，确保排序字段语义稳定。
 */
class AdItemBatchSortRequest extends FormRequest
{
    /**
     * 是否允许当前用户发起请求。
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * 请求参数校验规则。
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'items' => 'required|array|min:1',
            'items.*.adId' => 'required|integer|min:1|distinct',
            'items.*.sortNum' => 'required|integer|min:0',
        ];
    }

    /**
     * 自定义校验错误文案。
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'items.required' => '排序数据不能为空',
            'items.array' => '排序数据格式不正确',
            'items.min' => '排序数据不能为空',
            'items.*.adId.required' => '广告ID不能为空',
            'items.*.adId.integer' => '广告ID必须是整数',
            'items.*.adId.min' => '广告ID必须大于0',
            'items.*.adId.distinct' => '广告ID不能重复',
            'items.*.sortNum.required' => '排序值不能为空',
            'items.*.sortNum.integer' => '排序值必须是整数',
            'items.*.sortNum.min' => '排序值不能小于0',
        ];
    }
}
