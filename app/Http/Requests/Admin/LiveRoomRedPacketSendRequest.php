<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class LiveRoomRedPacketSendRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'roomId' => 'required|integer|min:1|exists:app_live_room,room_id',
            'title' => 'required|string|max:100',
            'content' => 'required|string|max:500',
            'totalAmount' => 'required|numeric|min:0.01',
            'packetCount' => 'required|integer|min:1',
            'expireSeconds' => 'required|integer|min:1|max:86400',
            'extra' => 'nullable|array',
        ];
    }

    public function messages()
    {
        return [
            'roomId.required' => '直播间ID不能为空',
            'roomId.integer' => '直播间ID必须是整数',
            'roomId.min' => '直播间ID无效',
            'roomId.exists' => '直播间不存在',
            'title.required' => '红包标题不能为空',
            'title.max' => '红包标题不能超过100个字符',
            'content.required' => '红包内容不能为空',
            'content.max' => '红包内容不能超过500个字符',
            'totalAmount.required' => '红包总金额不能为空',
            'totalAmount.numeric' => '红包总金额格式错误',
            'totalAmount.min' => '红包总金额必须大于0',
            'packetCount.required' => '红包个数不能为空',
            'packetCount.integer' => '红包个数必须是整数',
            'packetCount.min' => '红包个数必须大于0',
            'expireSeconds.required' => '红包有效时长不能为空',
            'expireSeconds.integer' => '红包有效时长必须是整数',
            'expireSeconds.min' => '红包有效时长必须大于0',
            'expireSeconds.max' => '红包有效时长不能超过86400秒',
            'extra.array' => '扩展字段格式错误',
        ];
    }
}
