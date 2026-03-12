<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Traits\AdminPostStoreRequestTrait;
use App\Http\Requests\App\ImageTextPostStoreRequest as AppImageTextPostStoreRequest;

/**
 * 后台发布图文帖子请求验证。
 *
 * 设计约束：
 * 1. 复用 App 图文发帖的内容、图片、话题等校验规则；
 * 2. 额外要求后台必须显式传入 memberId；
 * 3. memberId 仅允许官方正常账号，防止后台误用普通会员身份发帖。
 */
class PostStoreImageTextRequest extends AppImageTextPostStoreRequest
{
    use AdminPostStoreRequestTrait;

    /**
     * 获取请求校验规则。
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), $this->memberRules());
    }

    /**
     * 获取请求校验错误文案。
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), $this->memberMessages());
    }

    /**
     * 获取验证后的数据并补充后台发帖需要的 memberId。
     *
     * @return array<string, mixed>
     */
    public function validatedWithDefaults(): array
    {
        $data = parent::validatedWithDefaults();
        $data['memberId'] = (int) $this->input('memberId');

        return $data;
    }
}
