<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Traits\AdminPostStoreRequestTrait;
use App\Http\Requests\App\ArticlePostStoreRequest as AppArticlePostStoreRequest;

/**
 * 后台发布文章帖子请求验证。
 *
 * 设计约束：
 * 1. 复用 App 文章发帖校验与正文媒体解析能力；
 * 2. 后台必须传入 memberId 指定发帖人；
 * 3. memberId 仅允许官方正常账号，确保后台发帖身份可控。
 */
class PostStoreArticleRequest extends AppArticlePostStoreRequest
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
