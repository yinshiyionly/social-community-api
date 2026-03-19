<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class LiveRoomUpdateRequest extends FormRequest
{
    /**
     * 更新直播间请求鉴权。
     *
     * Admin 模块权限由路由中间件统一处理，请求层始终放行。
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * 更新直播间参数校验规则。
     *
     * 规则约束：
     * 1. roomId 必填，用于定位待更新直播间；
     * 2. isShowIndex 可选，未传时保持原值，避免误改历史展示策略；
     * 3. 时间字段若同时传入，结束时间必须晚于开始时间。
     *
     * @return array<string, string>
     */
    public function rules()
    {
        return [
            'roomId'             => 'required|integer|min:1',
            'roomTitle'          => 'nullable|string|max:200',
            'roomCover'          => 'nullable|string|max:500',
            'isShowIndex'        => 'sometimes|integer|in:0,1',
            'scheduledStartTime' => 'nullable|date',
            'scheduledEndTime'   => 'nullable|date|after:scheduledStartTime'
        ];
    }

    /**
     * 更新直播间参数校验错误文案。
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'roomId.required'              => '直播间ID不能为空',
            'roomId.integer'               => '直播间ID必须是整数',
            'roomId.min'                   => '直播间ID无效',
            'roomTitle.max'                => '直播间标题不能超过200个字符',
            'roomCover.max'                => '封面地址不能超过500个字符',
            'isShowIndex.integer'          => '是否展示在首页值无效',
            'isShowIndex.in'               => '是否展示在首页值无效',
            'scheduledStartTime.date'      => '计划开始时间格式无效',
            'scheduledEndTime.date'        => '计划结束时间格式无效',
            'scheduledEndTime.after'       => '计划结束时间必须晚于开始时间',
        ];
    }
}
