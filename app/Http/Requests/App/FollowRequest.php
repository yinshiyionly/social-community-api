<?php

namespace App\Http\Requests\App;

use App\Models\App\AppMemberFollow;
use Illuminate\Foundation\Http\FormRequest;

/**
 * 关注用户请求验证
 */
class FollowRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'source' => 'sometimes|string|in:search,recommend,profile,qr,post,comment',
        ];
    }

    public function messages()
    {
        return [
            'source.string' => '来源必须是字符串',
            'source.in' => '来源值无效',
        ];
    }

    /**
     * 获取来源值，默认为 profile
     *
     * @return string
     */
    public function getSource(): string
    {
        return $this->input('source', AppMemberFollow::SOURCE_PROFILE);
    }
}
