<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 后台系统消息列表请求校验。
 *
 * 约束目标：
 * 1. 统一分页参数与筛选参数格式；
 * 2. 默认补齐 isBroadcast=1，确保列表默认展示广播消息；
 * 3. 保证时间区间合法，避免结束时间早于开始时间。
 */
class MessageSystemListRequest extends FormRequest
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
     * 参数校验规则。
     *
     * @return array<string, string>
     */
    public function rules()
    {
        return [
            'pageNum' => 'nullable|integer|min:1',
            'pageSize' => 'nullable|integer|min:1|max:100',
            'isBroadcast' => 'nullable|integer|in:0,1',
            'memberId' => 'nullable|integer|min:1',
            'beginTime' => 'nullable|date',
            'endTime' => 'nullable|date',
            'isRead' => 'nullable|integer|in:0,1',
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
            'pageNum.integer' => '页码必须是整数',
            'pageNum.min' => '页码不能小于1',

            'pageSize.integer' => '每页条数必须是整数',
            'pageSize.min' => '每页条数不能小于1',
            'pageSize.max' => '每页条数不能超过100',

            'isBroadcast.integer' => '广播筛选值无效',
            'isBroadcast.in' => '广播筛选值仅支持0或1',

            'memberId.integer' => '会员ID必须是整数',
            'memberId.min' => '会员ID必须大于0',

            'beginTime.date' => '开始时间格式无效',
            'endTime.date' => '结束时间格式无效',

            'isRead.integer' => '已读筛选值无效',
            'isRead.in' => '已读筛选值仅支持0或1',
        ];
    }

    /**
     * 预处理默认参数。
     *
     * 关键规则：
     * - 当调用方未显式传入 isBroadcast 时，默认按广播消息查询，
     *   与后台“默认展示广播列表”的产品约定保持一致。
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $isBroadcast = $this->input('isBroadcast');
        if ($isBroadcast === null || $isBroadcast === '') {
            $this->merge(['isBroadcast' => 1]);
        }
    }

    /**
     * 增加时间区间跨字段校验。
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $beginTime = $this->input('beginTime');
            $endTime = $this->input('endTime');

            if (empty($beginTime) || empty($endTime)) {
                return;
            }

            if (strtotime((string) $endTime) < strtotime((string) $beginTime)) {
                $validator->errors()->add('endTime', '结束时间不能早于开始时间');
            }
        });
    }
}
