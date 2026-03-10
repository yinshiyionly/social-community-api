<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class LiveRoomStoreRequest extends FormRequest
{
    /**
     * 直播间创建鉴权。
     *
     * Admin 模块权限由路由中间件统一控制，请求层始终放行。
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * 创建直播间参数校验规则。
     *
     * 规则约束：
     * 1. 基础字段在两种直播类型下均必填，避免创建后详情页字段缺失；
     * 2. 伪直播仅允许两种素材来源：回放教室号或点播视频ID；
     * 3. 主播模式下忽略 mock 字段，避免历史前端冗余字段导致创建失败。
     *
     * @return array<string, string>
     */
    public function rules()
    {
        return [
            'roomTitle'          => 'required|string|max:200',
            'liveType'           => 'required|integer|in:1,2',
            'roomCover'          => 'required|string|max:500',
            'roomIntro'          => 'nullable|string|max:5000',
            'anchorName'         => 'required|string|max:100',
            'anchorAvatar'       => 'nullable|string|max:500',
            'scheduledStartTime' => 'required|date',
            'scheduledEndTime'   => 'required|date|after:scheduledStartTime',
            'enableLiveSell'     => 'required|integer|in:0,1,2',
            'mockVideoSource'    => 'exclude_unless:liveType,2|required|integer|in:1,2',
            'mockRoomId'         => 'exclude_unless:liveType,2|exclude_unless:mockVideoSource,1|required|integer',
            'mockVideoId'        => 'exclude_unless:liveType,2|exclude_unless:mockVideoSource,2|required|integer',
        ];
    }

    /**
     * 创建直播间参数校验错误文案。
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'roomTitle.required'          => '直播间标题不能为空',
            'roomTitle.max'               => '直播间标题不能超过200个字符',
            'liveType.required'           => '直播类型不能为空',
            'liveType.integer'            => '直播类型值无效',
            'liveType.in'                 => '直播类型值无效',
            'roomCover.required'          => '直播封面不能为空',
            'roomCover.max'               => '封面地址不能超过500个字符',
            'anchorName.required'         => '主播名称不能为空',
            'anchorName.max'              => '主播名称不能超过100个字符',
            'anchorAvatar.max'            => '主播头像地址不能超过500个字符',
            'scheduledStartTime.required' => '直播开始时间不能为空',
            'scheduledStartTime.date'     => '直播开始时间格式无效',
            'scheduledEndTime.required'   => '直播结束时间不能为空',
            'scheduledEndTime.date'       => '直播结束时间格式无效',
            'scheduledEndTime.after'      => '直播结束时间必须晚于开始时间',
            'enableLiveSell.required'     => '直播带货模板属性不能为空',
            'enableLiveSell.integer'      => '直播带货模板属性值无效',
            'enableLiveSell.in'           => '直播带货模板属性值无效',
            'mockVideoSource.required'    => '伪直播视频来源不能为空',
            'mockVideoSource.integer'     => '伪直播视频来源值无效',
            'mockVideoSource.in'          => '伪直播视频来源值无效',
            'mockRoomId.required'         => '伪直播关联的回放教室号不能为空',
            'mockRoomId.integer'          => '伪直播关联的回放教室号必须是整数',
            'mockVideoId.required'        => '伪直播视频ID不能为空',
            'mockVideoId.integer'         => '伪直播视频ID必须是整数',
        ];
    }
}
