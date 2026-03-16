<?php

namespace App\Http\Requests\App;

use Illuminate\Foundation\Http\FormRequest;

/**
 * App 端删除帖子请求验证。
 *
 * 规则约束：
 * 1. postId 必填，支持数字或数字字符串；
 * 2. postType 可选，传入时仅支持 1(图文)/2(视频)/3(文章)。
 */
class PostDeleteRequest extends FormRequest
{
    /**
     * 当前接口仅允许登录用户访问，鉴权由路由中间件 app.auth 负责。
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 定义删除帖子接口参数规则。
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'postId' => 'required|numeric|min:1',
            'postType' => 'sometimes|integer|in:1,2,3',
        ];
    }

    /**
     * 自定义参数错误提示。
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'postId.required' => 'postId不能为空',
            'postId.numeric' => 'postId格式错误',
            'postId.min' => 'postId必须大于0',
            'postType.integer' => 'postType必须是整数',
            'postType.in' => 'postType仅支持1(图文)、2(视频)、3(文章)',
        ];
    }
}
