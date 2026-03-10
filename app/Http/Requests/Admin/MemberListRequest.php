<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 后台会员列表请求参数校验。
 *
 * 职责：
 * 1. 约束后台分页与筛选字段格式；
 * 2. 统一处理时间范围合法性，避免无效时间条件下沉到查询层。
 */
class MemberListRequest extends FormRequest
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
     * 列表参数校验规则。
     *
     * @return array<string, string>
     */
    public function rules()
    {
        return [
            'pageNum' => 'nullable|integer|min:1',
            'pageSize' => 'nullable|integer|min:1|max:100',
            'memberId' => 'nullable|integer|min:1',
            'phone' => 'nullable|string|max:20',
            'nickname' => 'nullable|string|max:50',
            'status' => 'nullable|integer|in:1,2',
            'isOfficial' => 'nullable|integer|in:0,1',
            'beginTime' => 'nullable|date',
            'endTime' => 'nullable|date',
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
            'memberId.integer' => '用户ID必须是整数',
            'memberId.min' => '用户ID无效',
            'phone.max' => '手机号长度不能超过20位',
            'nickname.max' => '昵称长度不能超过50位',
            'status.integer' => '状态值无效',
            'status.in' => '状态值无效',
            'isOfficial.integer' => '官方账号标识值无效',
            'isOfficial.in' => '官方账号标识值无效',
            'beginTime.date' => '开始时间格式无效',
            'endTime.date' => '结束时间格式无效',
        ];
    }

    /**
     * 增加时间区间的跨字段校验。
     *
     * 关键约束：
     * - beginTime/endTime 同时存在时，结束时间不能早于开始时间，
     *   避免前端误传参数导致“看似成功但无结果”的问题。
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

