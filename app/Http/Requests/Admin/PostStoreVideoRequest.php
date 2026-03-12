<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Traits\AdminPostStoreRequestTrait;
use App\Http\Requests\App\VideoPostStoreRequest as AppVideoPostStoreRequest;

/**
 * 后台发布视频帖子请求验证。
 *
 * 设计约束：
 * 1. 复用 App 视频发帖对 videoUrl、coverUrl、content 的校验；
 * 2. 后台必须指定 memberId 作为发帖人；
 * 3. memberId 仅允许官方正常账号，避免越权代发。
 */
class PostStoreVideoRequest extends AppVideoPostStoreRequest
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
