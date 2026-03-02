<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class LiveRoomStatusRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'roomId' => 'required|integer|min:1',
            'status' => 'required|integer|in:0,1',
        ];
    }

    public function messages()
    {
        return [
            'roomId.required' => '直播间ID不能为空',
            'roomId.integer'  => '直播间ID必须是整数',
            'status.required' => '状态不能为空',
            'status.in'       => '状态值无效',
        ];
    }
}
